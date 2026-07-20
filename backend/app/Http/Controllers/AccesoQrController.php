<?php

namespace App\Http\Controllers;

use App\Models\AccesoQr;
use Illuminate\Http\Request;

class AccesoQrController extends Controller
{
    public function index(Request $request)
    {
        $query = AccesoQr::with('trabajador.persona');

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->whereHas('trabajador.persona', function ($q) use ($buscar) {
                $q->where('nombres', 'like', "%{$buscar}%")
                    ->orWhere('apellidos', 'like', "%{$buscar}%")
                    ->orWhere('dni', 'like', "%{$buscar}%");
            });
        }

        $accesos = $query->orderBy('fecha_acceso', 'desc')->paginate(20);

        return response()->json($accesos);
    }

    public function registrar(Request $request, $trabajadorId)
    {
        $acceso = AccesoQr::create([
            'trabajador_id' => $trabajadorId,
            'ip' => $request->ip(),
            'navegador' => $request->userAgent(),
            'fecha_acceso' => now(),
        ]);

        return response()->json($acceso, 201);
    }
}
