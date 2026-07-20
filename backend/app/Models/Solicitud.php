<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    public $timestamps = false;

    protected $table = 'solicitudes';

    protected $fillable = [
        'codigo', 'vinculo', 'persona_id', 'tipo_solicitud_id', 'estado', 'oficina_actual_id',
        'motivo_solicitud', 'tipo_cuenta', 'sistema_especifico', 'usuario_creado',
        'adjuntos', 'observaciones', 'correo_personal', 'oficina_sopporte', 'dificultad', 'atendido_por', 'fecha_solicitud', 'fecha_atencion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_solicitud' => 'datetime',
            'fecha_atencion' => 'datetime',
            'usuario_creado' => 'boolean',
        ];
    }

    public function getAdjuntosAttribute($value)
    {
        if (! $value) {
            return null;
        }

        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $value;
        }

        return $decoded;
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }

    public function tipoSolicitud()
    {
        return $this->belongsTo(TipoSolicitud::class);
    }

    public function oficinaActual()
    {
        return $this->belongsTo(Oficina::class, 'oficina_actual_id');
    }

    public function atendidoPor()
    {
        return $this->belongsTo(Usuario::class, 'atendido_por');
    }

    public function derivaciones()
    {
        return $this->hasMany(SolicitudDerivacion::class);
    }
}
