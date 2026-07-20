<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CorreoPersona extends Model
{
    public $timestamps = false;

    protected $table = 'correos_persona';

    protected $fillable = [
        'persona_id', 'correo', 'tipo', 'principal', 'estado',
    ];

    protected function casts(): array
    {
        return [
            'principal' => 'boolean',
        ];
    }

    public static function determinarTipo(string $correo): string
    {
        $domain = strtolower(substr(strrchr($correo, '@'), 1));
        $institucionales = ['unap.edu.pe', 'est.unap.pe'];
        foreach ($institucionales as $inst) {
            if ($domain === $inst || str_ends_with($domain, '.'.$inst)) {
                return 'INSTITUCIONAL';
            }
        }

        return 'PERSONAL';
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }
}
