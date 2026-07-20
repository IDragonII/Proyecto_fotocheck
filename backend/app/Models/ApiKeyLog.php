<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiKeyLog extends Model
{
    public $timestamps = false;

    protected $table = 'api_key_logs';

    protected $fillable = [
        'api_key_id', 'accion', 'ip', 'navegador', 'payload', 'response_status', 'fecha',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
        ];
    }

    public function apiKey()
    {
        return $this->belongsTo(ApiKey::class);
    }
}
