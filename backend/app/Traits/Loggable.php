<?php

namespace App\Traits;

use App\Models\Log;
use Illuminate\Http\Request;

trait Loggable
{
    protected function log(Request $request, string $accion, string $tabla, $registroId = null, string $detalle = '')
    {
        Log::create([
            'usuario_id' => $request->user()?->id ?? session('usuario_id'),
            'accion' => $accion,
            'tabla_afectada' => $tabla,
            'registro_id' => $registroId,
            'detalle' => $detalle,
            'ip' => $request->ip(),
        ]);
    }
}
