<?php

namespace App\Http\Controllers;

use App\Jobs\SendSolicitudEmailJob;
use App\Models\ApiKey;
use App\Models\ApiKeyLog;
use App\Models\CorreoPersona;
use App\Models\Persona;
use App\Models\Solicitud;
use App\Models\TipoSolicitud;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExternalTicketController extends Controller
{
    public function crearTicket(Request $request): JsonResponse
    {
        $apiKey = $request->attributes->get('api_key');

        if (! $apiKey->tienePermiso('tickets_crear')) {
            return response()->json(['mensaje' => 'No tiene permiso para crear tickets'], 403);
        }

        $request->validate([
            'dni' => 'nullable|string|max:8',
            'tipo_solicitud_id' => 'required|exists:tipo_solicitudes,id',
            'vinculo' => 'nullable|string|max:100',
            'motivo_solicitud' => 'nullable|string|max:255',
            'tipo_cuenta' => 'nullable|string|max:255',
            'sistema_especifico' => 'nullable|string|max:255',
            'adjuntos' => 'nullable|array|max:5',
            'adjuntos.*' => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
            'adjuntos_url' => 'nullable|array|max:5',
            'adjuntos_url.*' => 'url|max:5000',
            'correo_personal' => 'nullable|email|max:255',
            'oficina_sopporte' => 'nullable|string|max:255',
            'dificultad' => 'nullable|string|max:50',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        $tipoSolicitud = TipoSolicitud::with('oficina')->find($request->tipo_solicitud_id);
        $esSoporte = $tipoSolicitud->nombre === 'SOPORTE TECNICO';
        $esCorreo = $tipoSolicitud->nombre === 'SOLICITUD DE CORREO';
        $esCuenta = $tipoSolicitud->nombre === 'SOLICITUD DE ALTA Y BAJA';

        if ($esSoporte) {
            $request->validate([
                'oficina_sopporte' => 'required|string|max:255',
                'dificultad' => 'required|in:BAJA,MEDIA,ALTA,CRITICA',
                'observaciones' => 'required|string|max:1000',
            ]);
        } elseif ($esCorreo) {
            $request->validate([
                'motivo_solicitud' => 'required|in:CREACION,RESTABLECIMIENTO,ACTIVACION,OTRO',
                'correo_personal' => 'required|email|max:255',
            ]);
        } elseif ($esCuenta) {
            $request->validate([
                'motivo_solicitud' => 'required|in:CREACION,RENOVACION,MODIFICACION,BAJA',
            ]);
        } else {
            if (! $request->dni) {
                return response()->json(['mensaje' => 'El campo dni es requerido para este tipo de solicitud'], 422);
            }
        }

        $persona = null;
        if ($request->dni) {
            $persona = Persona::where('dni', $request->dni)->first();
            if (! $persona) {
                return response()->json(['mensaje' => 'Persona no encontrada con el DNI proporcionado'], 404);
            }
        }

        $ultimoCodigo = Solicitud::where('codigo', 'like', 'TICK-%')
            ->orderByDesc('id')
            ->value('codigo');

        if ($ultimoCodigo) {
            $partes = explode('-', $ultimoCodigo);
            $numero = (int) end($partes) + 1;
        } else {
            $numero = 1;
        }
        $codigo = 'TICK-'.date('Y').'-'.str_pad($numero, 3, '0', STR_PAD_LEFT);

        $solicitud = Solicitud::create([
            'codigo' => $codigo,
            'vinculo' => $request->vinculo,
            'persona_id' => $persona?->id,
            'tipo_solicitud_id' => $tipoSolicitud->id,
            'oficina_actual_id' => $tipoSolicitud->oficina_id,
            'estado' => 'PENDIENTE',
            'motivo_solicitud' => $request->motivo_solicitud,
            'tipo_cuenta' => $request->tipo_cuenta,
            'sistema_especifico' => $request->sistema_especifico,
            'adjuntos' => $this->guardarAdjuntos($request, $codigo),
            'observaciones' => $request->observaciones,
            'correo_personal' => $request->correo_personal,
            'oficina_sopporte' => $request->oficina_sopporte,
            'dificultad' => $request->dificultad,
            'fecha_solicitud' => now(),
        ]);

        if ($persona && $request->correo_personal) {
            $correoExiste = $persona->correos()
                ->where('correo', $request->correo_personal)
                ->exists();

            if (! $correoExiste) {
                CorreoPersona::create([
                    'persona_id' => $persona->id,
                    'correo' => $request->correo_personal,
                    'tipo' => CorreoPersona::determinarTipo($request->correo_personal),
                    'principal' => $persona->correos()->count() === 0,
                    'estado' => 'ACTIVO',
                ]);
            }
        }

        $tipoNombre = strtoupper(trim($tipoSolicitud->nombre ?? ''));

        if (! str_contains($tipoNombre, 'SOPORTE')) {
            SendSolicitudEmailJob::dispatch($solicitud->id);
        }

        $this->registrarLog($apiKey, 'crear_ticket', $request, 201, [
            'codigo' => $codigo,
            'dni' => $request->dni,
            'tipo_solicitud_id' => $tipoSolicitud->id,
        ]);

        return response()->json([
            'mensaje' => 'Ticket creado exitosamente',
            'data' => [
                'codigo' => $solicitud->codigo,
                'vinculo' => $solicitud->vinculo,
                'tipo_solicitud' => [
                    'id' => $tipoSolicitud->id,
                    'nombre' => $tipoSolicitud->nombre,
                    'oficina' => $tipoSolicitud->oficina->nombre,
                ],
                'estado' => $solicitud->estado,
                'adjuntos' => $solicitud->adjuntos,
                'observaciones' => $solicitud->observaciones,
                'correo_personal' => $solicitud->correo_personal,
                'oficina_sopporte' => $solicitud->oficina_sopporte,
                'dificultad' => $solicitud->dificultad,
                'fecha_solicitud' => $solicitud->fecha_solicitud,
            ],
        ], 201);
    }

    public function consultarTicket(string $codigo, Request $request)
    {
        $apiKey = $request->attributes->get('api_key');

        if (! $apiKey->tienePermiso('tickets_consultar')) {
            return response()->json(['mensaje' => 'No tiene permiso para consultar tickets'], 403);
        }

        $solicitud = Solicitud::with('persona', 'tipoSolicitud.oficina')
            ->where('codigo', $codigo)
            ->first();

        if (! $solicitud) {
            return response()->json(['mensaje' => 'Ticket no encontrado'], 404);
        }

        $this->registrarLog($apiKey, 'consultar_ticket', $request, 200, ['codigo' => $codigo]);

        return response()->json([
            'data' => [
                'codigo' => $solicitud->codigo,
                'vinculo' => $solicitud->vinculo,
                'tipo_solicitud' => [
                    'id' => $solicitud->tipoSolicitud->id,
                    'nombre' => $solicitud->tipoSolicitud->nombre,
                    'oficina' => $solicitud->tipoSolicitud->oficina->nombre,
                ],
                'estado' => $solicitud->estado,
                'adjuntos' => $solicitud->adjuntos,
                'observaciones' => $solicitud->observaciones,
                'correo_personal' => $solicitud->correo_personal,
                'oficina_sopporte' => $solicitud->oficina_sopporte,
                'dificultad' => $solicitud->dificultad,
                'fecha_solicitud' => $solicitud->fecha_solicitud,
                'fecha_atencion' => $solicitud->fecha_atencion,
                'persona' => [
                    'dni' => $solicitud->persona->dni ?? null,
                    'nombres' => $solicitud->persona->nombres ?? null,
                    'apellidos' => $solicitud->persona->apellidos ?? null,
                ],
            ],
        ]);
    }

    public function consultarPorDni(string $dni, Request $request)
    {
        $apiKey = $request->attributes->get('api_key');

        if (! $apiKey->tienePermiso('dni_consultar')) {
            return response()->json(['mensaje' => 'No tiene permiso para consultar datos por DNI'], 403);
        }

        $persona = Persona::with('correos')
            ->where('dni', $dni)
            ->first();

        if (! $persona) {
            return response()->json(['mensaje' => 'Persona no encontrada con el DNI proporcionado'], 404);
        }

        $correoPrincipal = $persona->correos->where('principal', true)->first()
            ?? $persona->correos->first();

        $correos = $persona->correos->map(fn ($c) => [
            'correo' => $c->correo,
            'tipo' => $c->tipo,
            'principal' => $c->principal,
        ]);

        $this->registrarLog($apiKey, 'consultar_dni', $request, 200, ['dni' => $dni]);

        return response()->json([
            'data' => [
                'dni' => $persona->dni,
                'nombres' => $persona->nombres,
                'apellidos' => $persona->apellidos,
                'telefono' => $persona->telefono,
                'direccion' => $persona->direccion,
                'correo' => $correoPrincipal->correo ?? null,
                'correos' => $correos,
            ],
        ]);
    }

    public function listarTipos(Request $request)
    {
        $apiKey = $request->attributes->get('api_key');

        if (! $apiKey->tienePermiso('tipos_solicitud_consultar')) {
            return response()->json(['mensaje' => 'No tiene permiso para consultar tipos de solicitud'], 403);
        }

        $tipos = TipoSolicitud::with('oficina')
            ->where('estado', 'ACTIVO')
            ->orderBy('nombre')
            ->get()
            ->map(fn ($tipo) => [
                'id' => $tipo->id,
                'nombre' => $tipo->nombre,
                'descripcion' => $tipo->descripcion,
                'oficina' => $tipo->oficina->nombre,
            ]);

        $this->registrarLog($apiKey, 'consultar_tipos_solicitud', $request, 200);

        return response()->json(['data' => $tipos]);
    }

    private function guardarAdjuntos(Request $request, string $codigo): ?string
    {
        $archivos = [];

        if ($request->hasFile('adjuntos')) {
            $disk = config('filesystems.disks.solicitudes.driver', 'local');
            foreach ($request->file('adjuntos') as $archivo) {
                $nombre = time().'_'.$archivo->getClientOriginalName();
                if ($disk === 'local') {
                    $path = $codigo.'/'.$nombre;
                    Storage::disk('solicitudes')->put($path, file_get_contents($archivo));
                } else {
                    $path = Storage::disk('solicitudes')->put($codigo.'/'.$nombre, $archivo);
                }
                $archivos[] = $path;
            }
        }

        if ($request->filled('adjuntos_url')) {
            foreach ($request->adjuntos_url as $url) {
                $archivos[] = $url;
            }
        }

        return $archivos ? json_encode($archivos) : null;
    }

    private function registrarLog(ApiKey $apiKey, string $accion, Request $request, int $status, array $payload = []): void
    {
        ApiKeyLog::create([
            'api_key_id' => $apiKey->id,
            'accion' => $accion,
            'ip' => $request->ip(),
            'navegador' => $request->userAgent(),
            'payload' => json_encode($payload),
            'response_status' => $status,
        ]);
    }
}
