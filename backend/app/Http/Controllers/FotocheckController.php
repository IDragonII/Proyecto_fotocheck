<?php

namespace App\Http\Controllers;

use App\Models\Fotocheck;
use App\Models\Trabajador;
use App\Traits\Loggable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FotocheckController extends Controller
{
    use Loggable;

    public function index(Request $request)
    {
        $query = Fotocheck::with('trabajador.persona');

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->whereHas('trabajador.persona', function ($q) use ($buscar) {
                $q->where('nombres', 'like', "%{$buscar}%")
                    ->orWhere('apellidos', 'like', "%{$buscar}%")
                    ->orWhere('dni', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $fotochecks = $query->orderBy('fecha_emision', 'desc')->paginate(15);

        return response()->json($fotochecks);
    }

    public function store(Request $request)
    {
        $request->validate([
            'trabajador_id' => 'required|exists:trabajadores,id',
        ]);

        $trabajador = Trabajador::with('persona')->findOrFail($request->trabajador_id);
        $codigo = 'FC-'.strtoupper(Str::random(8));
        $urlPublica = config('app.frontend_url', 'http://localhost:5173')."/{$trabajador->codigo_unico}";

        $fotocheck = Fotocheck::create([
            'trabajador_id' => $request->trabajador_id,
            'codigo' => $codigo,
            'url_qr' => $urlPublica,
            'estado' => 'VIGENTE',
        ]);

        $this->log($request, 'Creacion', 'fotochecks', $fotocheck->id, "Fotocheck creado: {$codigo} para {$trabajador->persona->nombres} {$trabajador->persona->apellidos}");

        return response()->json($fotocheck->load('trabajador.persona'), 201);
    }

    public function show($id)
    {
        $fotocheck = Fotocheck::with('trabajador.persona')->findOrFail($id);

        return response()->json($fotocheck);
    }

    public function destroy($id)
    {
        $fotocheck = Fotocheck::findOrFail($id);
        $fotocheck->update(['estado' => 'ANULADO']);
        $this->log(request(), 'Anulacion', 'fotochecks', $id, "Fotocheck anulado: {$fotocheck->codigo}");

        return response()->json(['message' => 'Fotocheck anulado']);
    }

    public function porTrabajador($trabajadorId)
    {
        $fotochecks = Fotocheck::where('trabajador_id', $trabajadorId)
            ->orderBy('fecha_emision', 'desc')
            ->get();

        return response()->json($fotochecks);
    }

    public function generar(Request $request)
    {
        $trabajadores = Trabajador::with('persona')->get();
        $creados = 0;

        foreach ($trabajadores as $t) {
            if ($t->persona && $t->persona->estado !== 'ACTIVO') {
                continue;
            }

            $tieneVigente = Fotocheck::where('trabajador_id', $t->id)
                ->where('estado', 'VIGENTE')
                ->exists();

            if ($tieneVigente || ! $t->codigo_unico) {
                continue;
            }

            $codigo = 'FC-'.strtoupper(Str::random(8));
            $urlPublica = config('app.frontend_url', 'http://localhost:5173')."/{$t->codigo_unico}";

            Fotocheck::create([
                'trabajador_id' => $t->id,
                'codigo' => $codigo,
                'url_qr' => $urlPublica,
                'estado' => 'VIGENTE',
            ]);

            $creados++;
        }

        $this->log($request, 'Generacion', 'fotochecks', null, "Fotochecks generados: {$creados}");

        return response()->json(['message' => "Fotochecks generados: {$creados}", 'creados' => $creados]);
    }
}
