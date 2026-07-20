<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usuarios';

    public $timestamps = false;

    protected $fillable = [
        'usuario',
        'clave',
        'nombres',
        'apellidos',
        'estado',
        'ultimo_acceso',
        'oficina_id',
    ];

    protected $hidden = [
        'clave',
    ];

    protected function casts(): array
    {
        return [
            'ultimo_acceso' => 'datetime',
            'fecha_creacion' => 'datetime',
        ];
    }

    public function getAuthPassword()
    {
        return $this->clave;
    }

    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'usuario_roles', 'usuario_id', 'rol_id');
    }

    public function oficina()
    {
        return $this->belongsTo(Oficina::class);
    }

    public function permisosExtras()
    {
        return $this->belongsToMany(Permiso::class, 'usuario_permisos', 'usuario_id', 'permiso_id')
            ->wherePivot('tipo', 'extra');
    }

    public function permisosNegados()
    {
        return $this->belongsToMany(Permiso::class, 'usuario_permisos', 'usuario_id', 'permiso_id')
            ->wherePivot('tipo', 'negado');
    }

    public function permisosUsuario()
    {
        return $this->belongsToMany(Permiso::class, 'usuario_permisos', 'usuario_id', 'permiso_id');
    }
}
