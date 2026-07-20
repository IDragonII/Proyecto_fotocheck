<?php

namespace App\Http\Controllers;

use App\Models\Oficina;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OficinaController extends Controller
{
    public function index(Request $request)
    {
        $query = Oficina::query();

        if ($buscar = $request->buscar) {
            $query->where('nombre', 'like', "%{$buscar}%");
        }

        return $query->orderBy('nombre')->paginate(15);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:150|unique:oficinas,nombre',
            'descripcion' => 'nullable|string|max:255',
            'estado' => 'sometimes|in:ACTIVO,INACTIVO',
        ]);

        $oficina = Oficina::create($request->only('nombre', 'descripcion', 'estado'));

        return response()->json($oficina, 201);
    }

    public function show(string $id)
    {
        $oficina = Oficina::withCount('solicitudes')->findOrFail($id);

        return $oficina;
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $oficina = Oficina::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:150|unique:oficinas,nombre,'.$id,
            'descripcion' => 'nullable|string|max:255',
            'estado' => 'sometimes|in:ACTIVO,INACTIVO',
        ]);

        $oficina->update($request->only('nombre', 'descripcion', 'estado'));

        return response()->json($oficina);
    }

    public function destroy(string $id): JsonResponse
    {
        $oficina = Oficina::findOrFail($id);

        if ($oficina->solicitudes()->exists()) {
            return response()->json([
                'mensaje' => 'No se puede eliminar: tiene solicitudes asociadas',
            ], 409);
        }

        $oficina->delete();

        return response()->json(['mensaje' => 'Oficina eliminada']);
    }
}
