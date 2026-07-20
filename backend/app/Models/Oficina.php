<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Oficina extends Model
{
    public $timestamps = false;

    protected $table = 'oficinas';

    protected $fillable = [
        'nombre', 'descripcion', 'estado',
    ];

    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class, 'oficina_actual_id');
    }

    public function usuarios()
    {
        return $this->hasMany(Usuario::class);
    }
}
