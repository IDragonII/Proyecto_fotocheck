<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fotocheck extends Model
{
    protected $table = 'fotochecks';

    public $timestamps = false;

    protected $fillable = [
        'trabajador_id', 'codigo', 'url_qr', 'qr_imagen',
        'fecha_emision', 'fecha_vencimiento', 'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_emision' => 'datetime',
            'fecha_vencimiento' => 'date',
        ];
    }

    public function trabajador()
    {
        return $this->belongsTo(Trabajador::class);
    }
}
