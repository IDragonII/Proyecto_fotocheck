<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization');

        if (! $authHeader || ! str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['mensaje' => 'Clave API requerida'], 401);
        }

        $clave = substr($authHeader, 7);

        $apiKey = ApiKey::validar($clave);

        if (! $apiKey) {
            return response()->json(['mensaje' => 'Clave API invalida o inactiva'], 401);
        }

        if ($apiKey->expira_en && $apiKey->expira_en->isPast()) {
            return response()->json(['mensaje' => 'Clave API expirada'], 403);
        }

        $apiKey->registrarUso();

        $request->attributes->set('api_key', $apiKey);

        return $next($request);
    }
}
