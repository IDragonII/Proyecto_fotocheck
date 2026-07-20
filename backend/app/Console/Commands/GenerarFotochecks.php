<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerarFotochecks extends Command
{
    protected $signature = 'fotocheck:generar {--dni= : Generar solo para un DNI especifico}';

    protected $description = 'Genera fotochecks para trabajadores que no tengan uno vigente';

    public function handle()
    {
        $query = DB::table('trabajadores')->where('estado', 'ACTIVO');

        if ($dni = $this->option('dni')) {
            $query->where('dni', $dni);
        }

        $trabajadores = $query->get();
        $creados = 0;

        foreach ($trabajadores as $t) {
            $tieneVigente = DB::table('fotochecks')
                ->where('trabajador_id', $t->id)
                ->where('estado', 'VIGENTE')
                ->exists();

            if ($tieneVigente) {
                $this->line("  {$t->nombres} {$t->apellidos} ya tiene fotocheck vigente, saltando...");

                continue;
            }

            $codigo = 'FC-'.strtoupper(Str::random(8));
            $urlPublica = config('app.frontend_url', 'http://localhost:5173')."/{$t->codigo_unico}";

            DB::table('fotochecks')->insert([
                'trabajador_id' => $t->id,
                'codigo' => $codigo,
                'url_qr' => $urlPublica,
                'estado' => 'VIGENTE',
                'fecha_emision' => now(),
            ]);

            $this->line("  {$t->nombres} {$t->apellidos} -> {$codigo}");
            $creados++;
        }

        $this->info("Fotochecks creados: {$creados}");
    }
}
