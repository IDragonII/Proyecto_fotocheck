<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerarQrFotochecks extends Command
{
    protected $signature = 'fotocheck:generar-qr {--domain= : Dominio base (ej: https://midominio.com)}';

    protected $description = 'Genera codigos QR para todos los fotochecks vigentes';

    public function handle()
    {
        $domain = $this->option('domain') ?? config('app.frontend_url', 'http://localhost:5173');

        $fotochecks = DB::table('fotochecks')
            ->join('trabajadores', 'trabajadores.id', '=', 'fotochecks.trabajador_id')
            ->where('fotochecks.estado', 'VIGENTE')
            ->select('fotochecks.*', 'trabajadores.codigo_unico', 'trabajadores.nombres', 'trabajadores.apellidos', 'trabajadores.dni')
            ->get();

        if ($fotochecks->isEmpty()) {
            $this->warn('No hay fotochecks vigentes para generar QR.');

            return;
        }

        $outputDir = public_path('qr');
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $this->info("Generando QR para {$fotochecks->count()} fotochecks...");

        foreach ($fotochecks as $f) {
            $url = "{$domain}/{$f->codigo_unico}";
            $filename = "qr_{$f->codigo_unico}.svg";

            $svg = \QrCode::format('svg')
                ->size(300)
                ->generate($url);

            file_put_contents("{$outputDir}/{$filename}", $svg);

            DB::table('fotochecks')->where('id', $f->id)->update(['url_qr' => $url]);

            $this->line("  {$f->nombres} {$f->apellidos} ({$f->dni}) -> {$filename}");
        }

        $this->info("QR generados en: {$outputDir}");
    }
}
