<?php

namespace App\Http\Controllers;

use App\Models\Fotocheck;
use App\Models\Trabajador;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        DB::statement("SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'");

        $totalTrabajadores = Trabajador::count();
        $trabajadoresActivos = Trabajador::whereHas('persona', function ($q) {
            $q->where('estado', 'ACTIVO');
        })->count();
        $totalFotochecks = Fotocheck::count();
        $fotochecksVigentes = Fotocheck::where('estado', 'VIGENTE')->count();
        $totalUsuarios = Usuario::count();
        $totalAccesos = DB::table('accesos_qr')->count();

        $personalPorTipo = DB::table('trabajadores')
            ->select(DB::raw("CASE WHEN LOWER(cargo) LIKE '%docente%' THEN 'Docentes' ELSE 'Administrativos' END as tipo"), DB::raw('count(*) as total'))
            ->groupByRaw("CASE WHEN LOWER(cargo) LIKE '%docente%' THEN 'Docentes' ELSE 'Administrativos' END")
            ->get();

        $fotosPorTipo = DB::table('trabajadores')
            ->join('personas', 'trabajadores.persona_id', '=', 'personas.id')
            ->select(
                DB::raw("SUM(CASE WHEN personas.url_foto_presencial IS NOT NULL AND personas.url_foto_presencial != '' THEN 1 ELSE 0 END) as presencial"),
                DB::raw("SUM(CASE WHEN personas.url_foto_virtual IS NOT NULL AND personas.url_foto_virtual != '' THEN 1 ELSE 0 END) as digital"),
                DB::raw("SUM(CASE WHEN (personas.url_foto_presencial IS NULL OR personas.url_foto_presencial = '') AND (personas.url_foto_virtual IS NULL OR personas.url_foto_virtual = '') THEN 1 ELSE 0 END) as sin_foto")
            )
            ->first();

        $disponibilidadFoto = DB::table('trabajadores')
            ->join('personas', 'trabajadores.persona_id', '=', 'personas.id')
            ->select(DB::raw("
                CASE
                    WHEN personas.url_foto_presencial IS NOT NULL AND personas.url_foto_presencial != '' THEN 'Con Fotografia'
                    ELSE 'Sin Fotografia'
                END as tipo
            "), DB::raw('count(*) as total'))
            ->groupByRaw("
                CASE
                    WHEN personas.url_foto_presencial IS NOT NULL AND personas.url_foto_presencial != '' THEN 'Con Fotografia'
                    ELSE 'Sin Fotografia'
                END
            ")
            ->get();

        $distribucionCargo = DB::table('trabajadores')
            ->select(DB::raw("CASE WHEN cargo IS NULL OR cargo = '' THEN 'Sin especificar' ELSE cargo END as cargo"), DB::raw('count(*) as total'))
            ->groupByRaw("CASE WHEN cargo IS NULL OR cargo = '' THEN 'Sin especificar' ELSE cargo END")
            ->orderByDesc('total')
            ->get();

        $integridadContacto = DB::table('trabajadores')
            ->join('personas', 'trabajadores.persona_id', '=', 'personas.id')
            ->leftJoin('correos_persona', function ($join) {
                $join->on('correos_persona.persona_id', '=', 'personas.id')
                    ->where('correos_persona.principal', '=', 1);
            })
            ->select(DB::raw("
                CASE
                    WHEN correos_persona.correo IS NOT NULL AND correos_persona.correo != '' AND personas.telefono IS NOT NULL AND personas.telefono != '' THEN 'Completo'
                    WHEN correos_persona.correo IS NOT NULL AND correos_persona.correo != '' THEN 'Solo Correo'
                    WHEN personas.telefono IS NOT NULL AND personas.telefono != '' THEN 'Solo Telefono'
                    ELSE 'Sin Contacto'
                END as estado
            "), DB::raw('count(*) as total'))
            ->groupByRaw("
                CASE
                    WHEN correos_persona.correo IS NOT NULL AND correos_persona.correo != '' AND personas.telefono IS NOT NULL AND personas.telefono != '' THEN 'Completo'
                    WHEN correos_persona.correo IS NOT NULL AND correos_persona.correo != '' THEN 'Solo Correo'
                    WHEN personas.telefono IS NOT NULL AND personas.telefono != '' THEN 'Solo Telefono'
                    ELSE 'Sin Contacto'
                END
            ")
            ->get();

        return response()->json([
            'totalTrabajadores' => $totalTrabajadores,
            'trabajadoresActivos' => $trabajadoresActivos,
            'totalFotochecks' => $totalFotochecks,
            'fotochecksVigentes' => $fotochecksVigentes,
            'totalUsuarios' => $totalUsuarios,
            'totalAccesos' => $totalAccesos,
            'personalPorTipo' => $personalPorTipo,
            'fotosPorTipo' => $fotosPorTipo,
            'disponibilidadFoto' => $disponibilidadFoto,
            'distribucionCargo' => $distribucionCargo,
            'integridadContacto' => $integridadContacto,
        ]);
    }
}
