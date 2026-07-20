<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiKeyController extends Controller
{
    public function index(Request $request)
    {
        $query = ApiKey::query();

        if ($buscar = $request->buscar) {
            $query->where('nombre', 'like', "%{$buscar}%");
        }

        $keys = $query->orderByDesc('created_at')->paginate(15);

        return $keys;
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'permisos' => 'required|array|min:1',
            'permisos.*' => 'string|in:tickets_crear,tickets_consultar,dni_consultar,tipos_solicitud_consultar',
            'rate_limit' => 'required|integer|min:1|max:10000',
            'tiempo_vida' => 'required|in:30,90,365,sin_expire',
        ]);

        $generada = ApiKey::generarClave();

        $expiraEn = $request->tiempo_vida === 'sin_expire'
            ? null
            : now()->addDays((int) $request->tiempo_vida);

        $apiKey = ApiKey::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'clave_hash' => $generada['clave_hash'],
            'clave_prefijo' => $generada['prefijo'],
            'permisos' => $request->permisos,
            'rate_limit' => $request->rate_limit,
            'estado' => 'ACTIVO',
            'expira_en' => $expiraEn,
        ]);

        return response()->json([
            'mensaje' => 'Clave API creada exitosamente',
            'data' => [
                'id' => $apiKey->id,
                'nombre' => $apiKey->nombre,
                'clave' => $generada['clave_raw'],
                'prefijo' => $apiKey->clave_prefijo,
                'permisos' => $apiKey->permisos,
                'rate_limit' => $apiKey->rate_limit,
                'estado' => $apiKey->estado,
                'expira_en' => $apiKey->expira_en,
            ],
        ], 201);
    }

    public function show(string $id)
    {
        $apiKey = ApiKey::findOrFail($id);

        return [
            'id' => $apiKey->id,
            'nombre' => $apiKey->nombre,
            'descripcion' => $apiKey->descripcion,
            'clave_prefijo' => $apiKey->clave_prefijo,
            'permisos' => $apiKey->permisos,
            'rate_limit' => $apiKey->rate_limit,
            'estado' => $apiKey->estado,
            'expira_en' => $apiKey->expira_en,
            'ultimo_uso' => $apiKey->ultimo_uso,
            'total_usos' => $apiKey->total_usos,
            'created_at' => $apiKey->created_at,
        ];
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $apiKey = ApiKey::findOrFail($id);

        $request->validate([
            'nombre' => 'sometimes|required|string|max:100',
            'descripcion' => 'nullable|string',
            'permisos' => 'sometimes|required|array|min:1',
            'permisos.*' => 'string|in:tickets_crear,tickets_consultar,dni_consultar,tipos_solicitud_consultar',
            'rate_limit' => 'sometimes|required|integer|min:1|max:10000',
            'tiempo_vida' => 'sometimes|required|in:30,90,365,sin_expire',
        ]);

        $data = $request->only(['nombre', 'descripcion', 'permisos', 'rate_limit']);

        if ($request->has('tiempo_vida')) {
            $data['expira_en'] = $request->tiempo_vida === 'sin_expire'
                ? null
                : now()->addDays((int) $request->tiempo_vida);
        }

        $apiKey->update($data);

        return response()->json([
            'mensaje' => 'Clave API actualizada',
            'data' => [
                'id' => $apiKey->id,
                'nombre' => $apiKey->nombre,
                'clave_prefijo' => $apiKey->clave_prefijo,
                'permisos' => $apiKey->permisos,
                'rate_limit' => $apiKey->rate_limit,
                'estado' => $apiKey->estado,
                'expira_en' => $apiKey->expira_en,
            ],
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $apiKey = ApiKey::findOrFail($id);
        $apiKey->delete();

        return response()->json(['mensaje' => 'Clave API eliminada']);
    }

    public function toggleEstado(string $id): JsonResponse
    {
        $apiKey = ApiKey::findOrFail($id);
        $apiKey->estado = $apiKey->estado === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';
        $apiKey->save();

        return response()->json([
            'mensaje' => 'Clave API '.($apiKey->estado === 'ACTIVO' ? 'activada' : 'desactivada'),
            'estado' => $apiKey->estado,
        ]);
    }

    public function regenerar(string $id): JsonResponse
    {
        $apiKey = ApiKey::findOrFail($id);
        $generada = ApiKey::generarClave();

        $apiKey->update([
            'clave_hash' => $generada['clave_hash'],
            'clave_prefijo' => $generada['prefijo'],
        ]);

        return response()->json([
            'mensaje' => 'Clave API regenerada',
            'data' => [
                'id' => $apiKey->id,
                'nombre' => $apiKey->nombre,
                'clave' => $generada['clave_raw'],
                'prefijo' => $apiKey->clave_prefijo,
            ],
        ]);
    }
}
