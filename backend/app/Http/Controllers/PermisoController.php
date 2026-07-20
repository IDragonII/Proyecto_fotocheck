<?php

namespace App\Http\Controllers;

use App\Models\Permiso;
use Illuminate\Http\Request;

class PermisoController extends Controller
{
    private function isSuperAdmin(Request $request): bool
    {
        return $request->user()->roles()->where('nombre', 'SUPER_ADMIN')->exists();
    }

    public function index()
    {
        $permisos = Permiso::orderBy('nombre')->get();

        return response()->json($permisos);
    }

    public function store(Request $request)
    {
        if (! $this->isSuperAdmin($request)) {
            $esCritico = $request->input('es_critico', false);
            if ($esCritico) {
                return response()->json([
                    'message' => 'Solo SUPER_ADMIN puede crear permisos críticos',
                ], 403);
            }
        }

        $request->validate([
            'nombre' => 'required|string|max:100|unique:permisos,nombre',
        ]);

        $permiso = Permiso::create($request->all());

        return response()->json($permiso, 201);
    }

    public function show($id)
    {
        $permiso = Permiso::findOrFail($id);

        return response()->json($permiso);
    }

    public function update(Request $request, $id)
    {
        $permiso = Permiso::findOrFail($id);

        if (! $this->isSuperAdmin($request) && $permiso->es_critico) {
            return response()->json([
                'message' => 'Solo SUPER_ADMIN puede modificar permisos críticos',
            ], 403);
        }

        $request->validate([
            'nombre' => 'required|string|max:100|unique:permisos,nombre,'.$id,
        ]);

        $permiso->update($request->all());

        return response()->json($permiso);
    }

    public function destroy($id)
    {
        $permiso = Permiso::findOrFail($id);

        if (! $this->isSuperAdmin(request()) && $permiso->es_critico) {
            return response()->json([
                'message' => 'Solo SUPER_ADMIN puede eliminar permisos críticos',
            ], 403);
        }

        $permiso->delete();

        return response()->json(['message' => 'Permiso eliminado']);
    }
}
