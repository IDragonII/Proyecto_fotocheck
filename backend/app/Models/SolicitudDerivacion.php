<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudDerivacion extends Model
{
    public $timestamps = false;

    protected $table = 'solicitud_derivaciones';

    protected $fillable = [
        'solicitud_id', 'oficina_origen_id', 'oficina_destino_id',
        'derivado_por', 'motivo', 'fecha',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
        ];
    }

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class);
    }

    public function oficinaOrigen()
    {
        return $this->belongsTo(Oficina::class, 'oficina_origen_id');
    }

    public function oficinaDestino()
    {
        return $this->belongsTo(Oficina::class, 'oficina_destino_id');
    }

    public function derivadoPor()
    {
        return $this->belongsTo(Usuario::class, 'derivado_por');
    }
}
