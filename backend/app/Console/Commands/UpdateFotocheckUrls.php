<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateFotocheckUrls extends Command
{
    protected $signature = 'fotocheck:update-urls';

    protected $description = 'Actualiza las URLs de fotochecks con codigo unico';

    public function handle()
    {
        $fotochecks = DB::table('fotochecks')->get();
        foreach ($fotochecks as $f) {
            $codigoUnico = DB::table('trabajadores')->where('id', $f->trabajador_id)->value('codigo_unico');
            if ($codigoUnico) {
                DB::table('fotochecks')->where('id', $f->id)->update([
                    'url_qr' => config('app.frontend_url')."/{$codigoUnico}",
                ]);
            }
        }
        $this->info("Actualizados {$fotochecks->count()} fotochecks");
    }
}
