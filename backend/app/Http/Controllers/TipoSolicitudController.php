<?php

namespace App\Http\Controllers;

use App\Models\TipoSolicitud;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TipoSolicitudController extends Controller
{
    public function index(Request $request)
    {
        $query = TipoSolicitud::with('oficina');

        if ($buscar = $request->buscar) {
            $query->where('nombre', 'like', "%{$buscar}%");
        }

        return $query->orderBy('nombre')->paginate(15);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:100|unique:tipo_solicitudes,nombre',
            'descripcion' => 'nullable|string|max:255',
            'oficina_id' => 'required|exists:oficinas,id',
            'estado' => 'sometimes|in:ACTIVO,INACTIVO',
        ]);

        $tipo = TipoSolicitud::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'oficina_id' => $request->oficina_id,
            'estado' => $request->estado ?? 'ACTIVO',
        ]);

        return response()->json($tipo->load('oficina'), 201);
    }

    public function show(string $id)
    {
        return TipoSolicitud::with('oficina')->findOrFail($id);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $tipo = TipoSolicitud::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:100|unique:tipo_solicitudes,nombre,'.$id,
            'descripcion' => 'nullable|string|max:255',
            'oficina_id' => 'required|exists:oficinas,id',
            'estado' => 'sometimes|in:ACTIVO,INACTIVO',
        ]);

        $tipo->update([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'oficina_id' => $request->oficina_id,
            'estado' => $request->estado ?? $tipo->estado,
        ]);

        return response()->json($tipo->load('oficina'));
    }

    public function destroy(string $id): JsonResponse
    {
        $tipo = TipoSolicitud::findOrFail($id);

        if ($tipo->solicitudes()->exists()) {
            return response()->json([
                'mensaje' => 'No se puede eliminar: tiene solicitudes asociadas',
            ], 409);
        }

        $tipo->delete();

        return response()->json(['mensaje' => 'Tipo de solicitud eliminado']);
    }
}
