<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estudiante extends Model
{
    protected $table = 'estudiantes';

    protected $fillable = [
        'persona_id', 'codigo_universitario', 'facultad', 'escuela_profesional',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }
}
