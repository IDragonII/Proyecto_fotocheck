<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    protected $table = 'personas';

    protected $fillable = [
        'dni', 'nombres', 'apellidos', 'telefono', 'direccion',
        'fecha_nacimiento', 'grupo_sanguineo', 'url_foto_presencial', 'url_foto_virtual', 'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
        ];
    }

    public function trabajadores()
    {
        return $this->hasMany(Trabajador::class);
    }

    public function estudiantes()
    {
        return $this->hasMany(Estudiante::class);
    }

    public function correos()
    {
        return $this->hasMany(CorreoPersona::class);
    }

    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class);
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombres} {$this->apellidos}";
    }
}
