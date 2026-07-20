<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoSolicitud extends Model
{
    public $timestamps = true;

    protected $table = 'tipo_solicitudes';

    protected $fillable = [
        'nombre', 'descripcion', 'oficina_id', 'estado',
    ];

    public function oficina()
    {
        return $this->belongsTo(Oficina::class);
    }

    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class, 'tipo_solicitud_id');
    }
}
