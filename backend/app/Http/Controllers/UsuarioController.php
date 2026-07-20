<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\Usuario;
use App\Traits\Loggable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    use Loggable;

    private function isSuperAdmin(Request $request): bool
    {
        return $request->user()->roles()->where('nombre', 'SUPER_ADMIN')->exists();
    }

    private function getUserLevel(Request $request): int
    {
        return $request->user()->roles()->max('nivel') ?? 0;
    }

    public function index(Request $request)
    {
        $query = Usuario::with('roles', 'oficina', 'permisosExtras', 'permisosNegados');

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('usuario', 'like', "%{$buscar}%")
                    ->orWhere('nombres', 'like', "%{$buscar}%")
                    ->orWhere('apellidos', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $usuarios = $query->orderBy('nombres')->paginate(15);

        return response()->json($usuarios);
    }

    public function store(Request $request)
    {
        $request->validate([
            'usuario' => 'required|string|max:50|unique:usuarios,usuario',
            'clave' => 'required|string|min:6',
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'oficina_id' => 'nullable|exists:oficinas,id',
            'roles' => 'required|array',
        ]);

        if (! $this->isSuperAdmin($request)) {
            $roleIds = $request->roles;
            $maxRoleLevel = Rol::whereIn('id', $roleIds)->max('nivel');

            if ($maxRoleLevel >= $this->getUserLevel($request)) {
                return response()->json([
                    'message' => 'No puedes asignar roles con nivel igual o superior al tuyo',
                ], 403);
            }
        }

        $usuario = Usuario::create([
            'usuario' => $request->usuario,
            'clave' => Hash::make($request->clave),
            'nombres' => $request->nombres,
            'apellidos' => $request->apellidos,
            'estado' => $request->estado ?? 'ACTIVO',
            'oficina_id' => $request->oficina_id,
        ]);

        $usuario->roles()->attach($request->roles);

        if ($this->isSuperAdmin($request)) {
            if ($request->has('permisos_extras')) {
                foreach ($request->permisos_extras as $permisoId) {
                    $usuario->permisosUsuario()->attach($permisoId, ['tipo' => 'extra']);
                }
            }
            if ($request->has('permisos_negados')) {
                foreach ($request->permisos_negados as $permisoId) {
                    $usuario->permisosUsuario()->attach($permisoId, ['tipo' => 'negado']);
                }
            }
        }

        $this->log($request, 'Creacion', 'usuarios', $usuario->id, "Usuario creado: {$usuario->usuario}");

        return response()->json($usuario->load('roles', 'oficina', 'permisosExtras', 'permisosNegados'), 201);
    }

    public function show($id)
    {
        $usuario = Usuario::with('roles', 'oficina', 'permisosExtras', 'permisosNegados')->findOrFail($id);

        return response()->json($usuario);
    }

    public function update(Request $request, $id)
    {
        $usuario = Usuario::findOrFail($id);
        $isSelf = $request->user()->id === $id;

        if ($isSelf && ! $this->isSuperAdmin($request)) {
            return response()->json([
                'message' => 'No puedes modificar tu propio usuario',
            ], 403);
        }

        $request->validate([
            'usuario' => 'required|string|max:50|unique:usuarios,usuario,'.$id,
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'oficina_id' => 'nullable|exists:oficinas,id',
        ]);

        $data = $request->except('clave', 'roles', 'permisos_extras');

        if ($request->filled('clave')) {
            $data['clave'] = Hash::make($request->clave);
        }

        $usuario->update($data);

        if ($request->has('roles') && ! $isSelf) {
            if (! $this->isSuperAdmin($request)) {
                $roleIds = $request->roles;
                $maxRoleLevel = Rol::whereIn('id', $roleIds)->max('nivel');

                if ($maxRoleLevel >= $this->getUserLevel($request)) {
                    return response()->json([
                        'message' => 'No puedes asignar roles con nivel igual o superior al tuyo',
                    ], 403);
                }
            }

            $usuario->roles()->sync($request->roles);
        }

        if ($this->isSuperAdmin($request)) {
            $usuario->permisosUsuario()->detach();
            if ($request->has('permisos_extras')) {
                foreach ($request->permisos_extras as $permisoId) {
                    $usuario->permisosUsuario()->attach($permisoId, ['tipo' => 'extra']);
                }
            }
            if ($request->has('permisos_negados')) {
                foreach ($request->permisos_negados as $permisoId) {
                    $usuario->permisosUsuario()->attach($permisoId, ['tipo' => 'negado']);
                }
            }
        }

        $this->log($request, 'Actualizacion', 'usuarios', $id, "Usuario actualizado: {$usuario->usuario}");

        return response()->json($usuario->load('roles', 'oficina', 'permisosExtras', 'permisosNegados'));
    }

    public function destroy(Request $request, $id)
    {
        $usuario = Usuario::findOrFail($id);

        if ($request->user()->id === $id) {
            return response()->json([
                'message' => 'No puedes eliminar tu propio usuario',
            ], 403);
        }

        $usuario->roles()->detach();
        $usuario->permisosUsuario()->detach();
        $usuario->delete();

        $this->log($request, 'Eliminacion', 'usuarios', $id, "Usuario eliminado: {$usuario->usuario}");

        return response()->json(['message' => 'Usuario eliminado']);
    }

    public function desbloquear(Request $request, $id)
    {
        $usuario = Usuario::findOrFail($id);

        if ($request->user()->id === $id) {
            return response()->json([
                'message' => 'No puedes desbloquear tu propio usuario',
            ], 403);
        }

        $usuario->update([
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
        ]);

        $this->log($request, 'Desbloqueo', 'usuarios', $id, "Usuario desbloqueado: {$usuario->usuario}");

        return response()->json(['message' => 'Usuario desbloqueado']);
    }
}
