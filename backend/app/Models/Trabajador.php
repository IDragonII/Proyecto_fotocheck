<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trabajador extends Model
{
    protected $table = 'trabajadores';

    public $timestamps = false;

    protected $fillable = [
        'persona_id', 'codigo_unico', 'codigo_nfs', 'empresa', 'area', 'dependencia',
        'cargo', 'regimen', 'resolucion_rectoral', 'vigencia', 'fecha_emision', 'fecha_ingreso',

    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'fecha_ingreso' => 'date',
            'fecha_emision' => 'date',
        ];
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }

    public function fotochecks()
    {
        return $this->hasMany(Fotocheck::class);
    }

    public function accesosQr()
    {
        return $this->hasMany(AccesoQr::class);
    }
}
