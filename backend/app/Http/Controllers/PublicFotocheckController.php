<?php

namespace App\Http\Controllers;

use App\Models\AccesoQr;
use App\Models\Fotocheck;
use App\Models\Trabajador;
use Illuminate\Http\Request;

class PublicFotocheckController extends Controller
{
    public function show($codigo, Request $request)
    {
        $trabajador = Trabajador::with('persona')->where('codigo_unico', $codigo)->first();

        if (! $trabajador) {
            return response()->json(['message' => 'Trabajador no encontrado'], 404);
        }

        $fotocheck = Fotocheck::where('trabajador_id', $trabajador->id)
            ->where('estado', 'VIGENTE')
            ->orderBy('fecha_emision', 'desc')
            ->first();

        if (! $fotocheck) {
            return response()->json(['message' => 'Fotocheck no encontrado'], 404);
        }

        $yaAccedio = AccesoQr::where('trabajador_id', $trabajador->id)
            ->where('ip', $request->ip())
            ->where('fecha_acceso', '>=', now()->subSeconds(5))
            ->exists();

        if (! $yaAccedio) {
            AccesoQr::create([
                'trabajador_id' => $trabajador->id,
                'ip' => $request->ip(),
                'navegador' => $request->userAgent(),
                'fecha_acceso' => now(),
            ]);
        }

        $persona = $trabajador->persona;
        $correo = $persona->correos()->where('principal', true)->first();
        $estudiante = $persona->estudiantes()->first();

        return response()->json([
            'trabajador' => [
                'dni' => $persona->dni,
                'nombres' => $persona->nombres,
                'apellidos' => $persona->apellidos,
                'nombre_completo' => $persona->nombre_completo,
                'telefono' => $persona->telefono,
                'direccion' => $persona->direccion,
                'url_foto_presencial' => $persona->url_foto_presencial,
                'url_foto_virtual' => $persona->url_foto_virtual,
                'correo' => $correo?->correo,
                'grupo_sanguineo' => $persona->grupo_sanguineo,
                'cargo' => $trabajador->cargo,
                'area' => $trabajador->area,
                'dependencia' => $trabajador->dependencia,
                'empresa' => $trabajador->empresa,
                'codigo' => $trabajador->codigo_unico,
                'codigo_universitario' => $estudiante?->codigo_universitario,
                'codigo_nfs' => $trabajador->codigo_nfs,
                'fecha_ingreso' => $trabajador->fecha_ingreso,
                'regimen' => $trabajador->regimen,
                'facultad' => $estudiante?->facultad,
                'escuela_profesional' => $estudiante?->escuela_profesional,
                'resolucion_rectoral' => $trabajador->resolucion_rectoral,
                'vigencia' => $trabajador->vigencia,
                'fecha_emision' => $trabajador->fecha_emision,
            ],
            'fotocheck' => [
                'codigo' => $fotocheck->codigo,
                'estado' => $fotocheck->estado,
                'fecha_emision' => $fotocheck->fecha_emision,
                'url_qr' => $fotocheck->url_qr,
                'qr_imagen' => $fotocheck->qr_imagen,
            ],
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }
}
