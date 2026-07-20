<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    protected $table = 'api_keys';

    protected $fillable = [
        'nombre', 'descripcion', 'clave_hash', 'clave_prefijo',
        'permisos', 'rate_limit', 'estado', 'expira_en',
    ];

    protected function casts(): array
    {
        return [
            'permisos' => 'array',
            'expira_en' => 'datetime',
            'ultimo_uso' => 'datetime',
            'total_usos' => 'integer',
            'rate_limit' => 'integer',
        ];
    }

    public function logs()
    {
        return $this->hasMany(ApiKeyLog::class);
    }

    public static function generarClave(): array
    {
        $claveRaw = 'fka_'.Str::random(44);
        $claveHash = hash('sha256', $claveRaw);
        $prefijo = substr($claveRaw, 0, 8);

        return [
            'clave_raw' => $claveRaw,
            'clave_hash' => $claveHash,
            'prefijo' => $prefijo,
        ];
    }

    public static function validar(string $clave): ?self
    {
        $hash = hash('sha256', $clave);

        $apiKey = static::where('clave_hash', $hash)->where('estado', 'ACTIVO')->first();

        if (! $apiKey) {
            return null;
        }

        if ($apiKey->expira_en && $apiKey->expira_en->isPast()) {
            return null;
        }

        $minutos = now()->subMinutes(1);
        $usosRecientes = static::where('id', $apiKey->id)
            ->whereHas('logs', function ($q) use ($minutos) {
                $q->where('fecha', '>=', $minutos);
            })
            ->withCount(['logs as usos_recientes' => function ($q) use ($minutos) {
                $q->where('fecha', '>=', $minutos);
            }])
            ->first();

        if ($usosRecientes && ($usosRecientes->usos_recientes ?? 0) >= $apiKey->rate_limit) {
            return null;
        }

        return $apiKey;
    }

    public function registrarUso(): void
    {
        $this->increment('total_usos');
        $this->update(['ultimo_uso' => now()]);
    }

    public function tienePermiso(string $permiso): bool
    {
        return in_array($permiso, $this->permisos ?? []);
    }
}
