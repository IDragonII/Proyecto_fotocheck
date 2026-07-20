<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        // SUPER_ADMIN tiene acceso total
        if ($user->roles()->where('nombre', 'SUPER_ADMIN')->exists()) {
            return $next($request);
        }

        // Verificar si el permiso está negado para el usuario
        $isDenied = $user->permisosNegados()
            ->where('nombre', $permission)
            ->exists();

        if ($isDenied) {
            return response()->json([
                'message' => 'No tienes permiso para realizar esta accion',
                'required' => $permission,
            ], 403);
        }

        // Verificar permiso por roles
        $hasByRole = $user->roles()
            ->whereHas('permisos', function ($q) use ($permission) {
                $q->where('nombre', $permission);
            })
            ->exists();

        if ($hasByRole) {
            return $next($request);
        }

        // Verificar permiso directo del usuario
        $hasDirect = $user->permisosExtras()
            ->where('nombre', $permission)
            ->exists();

        if ($hasDirect) {
            return $next($request);
        }

        return response()->json([
            'message' => 'No tienes permiso para realizar esta accion',
            'required' => $permission,
        ], 403);
    }
}
