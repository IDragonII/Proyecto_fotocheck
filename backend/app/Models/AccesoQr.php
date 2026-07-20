<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccesoQr extends Model
{
    protected $table = 'accesos_qr';

    public $timestamps = false;

    protected $fillable = ['trabajador_id', 'ip', 'navegador', 'fecha_acceso'];

    protected function casts(): array
    {
        return [
            'fecha_acceso' => 'datetime',
        ];
    }

    public function trabajador()
    {
        return $this->belongsTo(Trabajador::class);
    }
}
