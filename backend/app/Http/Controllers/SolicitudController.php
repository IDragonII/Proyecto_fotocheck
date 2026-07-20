<?php

namespace App\Http\Controllers;

use App\Jobs\SendSolicitudRespuestaJob;
use App\Models\Solicitud;
use App\Models\SolicitudDerivacion;
use App\Traits\Loggable;
use Illuminate\Http\Request;

class SolicitudController extends Controller
{
    use Loggable;

    private function isAdmin(Request $request): bool
    {
        return $request->user()->roles()->whereIn('nombre', ['SUPER_ADMIN', 'ADMIN'])->exists();
    }

    public function index(Request $request)
    {
        $query = Solicitud::with('persona.correos', 'tipoSolicitud', 'oficinaActual', 'atendidoPor');

        if (! $this->isAdmin($request)) {
            $oficinaId = $request->user()->oficina_id;
            if ($oficinaId) {
                $query->where('oficina_actual_id', $oficinaId);
            } else {
                return response()->json(['data' => []]);
            }
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('tipo_solicitud_id')) {
            $query->where('tipo_solicitud_id', $request->tipo_solicitud_id);
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo', 'like', "%{$buscar}%")
                    ->orWhereHas('persona', function ($pq) use ($buscar) {
                        $pq->where('dni', 'like', "%{$buscar}%")
                            ->orWhere('nombres', 'like', "%{$buscar}%")
                            ->orWhere('apellidos', 'like', "%{$buscar}%");
                    });
            });
        }

        $solicitudes = $query->orderBy('fecha_solicitud', 'desc')->paginate(15);

        return response()->json($solicitudes);
    }

    public function show($id, Request $request)
    {
        $solicitud = Solicitud::with([
            'persona',
            'tipoSolicitud.oficina',
            'oficinaActual',
            'atendidoPor',
            'derivaciones.oficinaOrigen',
            'derivaciones.oficinaDestino',
            'derivaciones.derivadoPor',
        ])->findOrFail($id);

        if (! $this->isAdmin($request)) {
            $oficinaId = $request->user()->oficina_id;
            if ($solicitud->oficina_actual_id != $oficinaId) {
                return response()->json(['message' => 'No tiene acceso a esta solicitud'], 403);
            }
        }

        return response()->json($solicitud);
    }

    public function update(Request $request, $id)
    {
        $solicitud = Solicitud::findOrFail($id);

        if (! $this->isAdmin($request)) {
            $oficinaId = $request->user()->oficina_id;
            if ($solicitud->oficina_actual_id != $oficinaId) {
                return response()->json(['message' => 'No tiene acceso a esta solicitud'], 403);
            }
        }

        $request->validate([
            'motivo_solicitud' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string|max:1000',
            'adjuntos' => 'nullable|string|max:5000',
            'usuario_creado' => 'nullable|boolean',
            'correo_personal' => 'nullable|email|max:255',
            'oficina_sopporte' => 'nullable|string|max:255',
            'dificultad' => 'nullable|string|max:50',
        ]);

        $solicitud->update([
            'motivo_solicitud' => $request->motivo_solicitud ?? $solicitud->motivo_solicitud,
            'observaciones' => $request->observaciones ?? $solicitud->observaciones,
            'adjuntos' => $request->adjuntos ?? $solicitud->adjuntos,
            'usuario_creado' => $request->usuario_creado ?? $solicitud->usuario_creado,
            'correo_personal' => $request->correo_personal ?? $solicitud->correo_personal,
            'oficina_sopporte' => $request->oficina_sopporte ?? $solicitud->oficina_sopporte,
            'dificultad' => $request->dificultad ?? $solicitud->dificultad,
        ]);

        $this->log($request, 'Actualizacion', 'solicitudes', $id, "Ticket actualizado: {$solicitud->codigo}");

        return response()->json($solicitud->load('persona', 'tipoSolicitud', 'oficinaActual', 'atendidoPor'));
    }

    public function derivar(Request $request, $id)
    {
        $solicitud = Solicitud::findOrFail($id);

        if (! $this->isAdmin($request)) {
            $oficinaId = $request->user()->oficina_id;
            if ($solicitud->oficina_actual_id != $oficinaId) {
                return response()->json(['message' => 'No tiene acceso a esta solicitud'], 403);
            }
        }

        $request->validate([
            'oficina_destino_id' => 'required|exists:oficinas,id',
            'motivo' => 'nullable|string|max:500',
        ]);

        if ($request->oficina_destino_id == $solicitud->oficina_actual_id) {
            return response()->json(['message' => 'La oficina destino es la misma que la actual'], 422);
        }

        SolicitudDerivacion::create([
            'solicitud_id' => $solicitud->id,
            'oficina_origen_id' => $solicitud->oficina_actual_id,
            'oficina_destino_id' => $request->oficina_destino_id,
            'derivado_por' => $request->user()->id,
            'motivo' => $request->motivo,
            'fecha' => now(),
        ]);

        $solicitud->update([
            'oficina_actual_id' => $request->oficina_destino_id,
            'estado' => $solicitud->estado === 'PENDIENTE' ? 'EN_PROCESO' : $solicitud->estado,
        ]);

        $this->log($request, 'Derivacion', 'solicitudes', $id, "Ticket {$solicitud->codigo} derivado a oficina {$request->oficina_destino_id}");

        return response()->json($solicitud->load('persona', 'tipoSolicitud', 'oficinaActual', 'derivaciones'));
    }

    public function resolver(Request $request, $id)
    {
        $solicitud = Solicitud::findOrFail($id);

        if (! $this->isAdmin($request)) {
            $oficinaId = $request->user()->oficina_id;
            if ($solicitud->oficina_actual_id != $oficinaId) {
                return response()->json(['message' => 'No tiene acceso a esta solicitud'], 403);
            }
        }

        $request->validate([
            'respuesta' => 'nullable|string|max:1000',
        ]);

        $solicitud->update([
            'estado' => 'RESUELTO',
            'fecha_atencion' => now(),
            'atendido_por' => $request->user()->id,
            'respuesta' => $request->respuesta,
        ]);

        $this->log($request, 'Resolucion', 'solicitudes', $id, "Ticket {$solicitud->codigo} marcado como RESUELTO");

        // Dispatch email job with the response
        if ($request->filled('respuesta') && $solicitud->tipoSolicitud->nombre !== 'SOPORTE TECNICO') {
            SendSolicitudRespuestaJob::dispatch($solicitud->id);
        }

        return response()->json($solicitud->load('persona', 'tipoSolicitud', 'oficinaActual', 'atendidoPor'));
    }

    public function rechazar(Request $request, $id)
    {
        $solicitud = Solicitud::findOrFail($id);

        if (! $this->isAdmin($request)) {
            $oficinaId = $request->user()->oficina_id;
            if ($solicitud->oficina_actual_id != $oficinaId) {
                return response()->json(['message' => 'No tiene acceso a esta solicitud'], 403);
            }
        }

        $request->validate([
            'respuesta' => 'nullable|string|max:1000',
        ]);

        $solicitud->update([
            'estado' => 'RECHAZADO',
            'fecha_atencion' => now(),
            'atendido_por' => $request->user()->id,
            'respuesta' => $request->respuesta,
        ]);

        $this->log($request, 'Rechazo', 'solicitudes', $id, "Ticket {$solicitud->codigo} marcado como RECHAZADO");

        // Dispatch email job with the response
        if ($request->filled('respuesta') && $solicitud->tipoSolicitud->nombre !== 'SOPORTE TECNICO') {
            SendSolicitudRespuestaJob::dispatch($solicitud->id);
        }

        return response()->json($solicitud->load('persona', 'tipoSolicitud', 'oficinaActual', 'atendidoPor'));
    }
}
