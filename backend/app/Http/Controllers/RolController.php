<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Traits\Loggable;
use Illuminate\Http\Request;

class RolController extends Controller
{
    use Loggable;

    private function getUserLevel(Request $request): int
    {
        $user = $request->user();

        return $user->roles()->max('nivel') ?? 0;
    }

    private function isSuperAdmin(Request $request): bool
    {
        return $request->user()->roles()->where('nombre', 'SUPER_ADMIN')->exists();
    }

    public function index()
    {
        $roles = Rol::with('permisos')->orderBy('nivel', 'desc')->get();

        return response()->json($roles);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100|unique:roles,nombre',
            'nivel' => 'required|integer|min:1|max:100',
        ]);

        $nivel = $request->nivel;
        $userLevel = $this->getUserLevel($request);

        if (! $this->isSuperAdmin($request) && $nivel >= $userLevel) {
            return response()->json([
                'message' => 'No puedes crear roles con nivel igual o superior al tuyo',
            ], 403);
        }

        $rol = Rol::create($request->all());

        if ($request->has('permisos')) {
            $rol->permisos()->attach($request->permisos);
        }

        $this->log($request, 'Creacion', 'roles', $rol->id, "Rol creado: {$rol->nombre}");

        return response()->json($rol->load('permisos'), 201);
    }

    public function show($id)
    {
        $rol = Rol::with('permisos')->findOrFail($id);

        return response()->json($rol);
    }

    public function update(Request $request, $id)
    {
        $rol = Rol::findOrFail($id);

        if (! $this->isSuperAdmin($request)) {
            if ($rol->nombre === 'SUPER_ADMIN') {
                return response()->json([
                    'message' => 'No puedes modificar el rol SUPER_ADMIN',
                ], 403);
            }

            $nivel = $request->nivel ?? $rol->nivel;
            $userLevel = $this->getUserLevel($request);

            if ($nivel >= $userLevel) {
                return response()->json([
                    'message' => 'No puedes asignar un nivel igual o superior al tuyo',
                ], 403);
            }
        }

        $request->validate([
            'nombre' => 'required|string|max:100|unique:roles,nombre,'.$id,
            'nivel' => 'required|integer|min:1|max:100',
        ]);

        $rol->update($request->except('permisos'));
        $rol->permisos()->sync($request->permisos ?? []);

        $this->log($request, 'Actualizacion', 'roles', $id, "Rol actualizado: {$rol->nombre}");

        return response()->json($rol->load('permisos'));
    }

    public function destroy($id)
    {
        $rol = Rol::findOrFail($id);

        if (! $this->isSuperAdmin(request())) {
            if ($rol->nombre === 'SUPER_ADMIN') {
                return response()->json([
                    'message' => 'No puedes eliminar el rol SUPER_ADMIN',
                ], 403);
            }

            if ($rol->nivel >= $this->getUserLevel(request())) {
                return response()->json([
                    'message' => 'No puedes eliminar un rol con nivel igual o superior al tuyo',
                ], 403);
            }
        }

        $rol->permisos()->detach();
        $rol->delete();

        $this->log(request(), 'Eliminacion', 'roles', $id, "Rol eliminado: {$rol->nombre}");

        return response()->json(['message' => 'Rol eliminado']);
    }
}
