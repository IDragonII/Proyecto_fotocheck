<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsuarioPermiso extends Model
{
    public $timestamps = false;

    protected $table = 'usuario_permisos';

    protected $fillable = [
        'usuario_id',
        'permiso_id',
        'tipo',
    ];
}
