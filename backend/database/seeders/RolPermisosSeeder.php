<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolPermisosSeeder extends Seeder
{
    public function run(): void
    {
        // SUPER_ADMIN - todos los permisos
        $todosLosPermisos = DB::table('permisos')->pluck('id');

        foreach ($todosLosPermisos as $permisoId) {
            DB::table('rol_permisos')->insert([
                'rol_id' => 1,
                'permiso_id' => $permisoId,
            ]);
        }

        // ADMIN - todo excepto gestión de roles y permisos
        $excluidos = [
            'roles_crear', 'roles_editar', 'roles_eliminar',
            'permisos_crear', 'permisos_editar', 'permisos_eliminar', 'permisos_asignar',
        ];

        $permisosAdmin = DB::table('permisos')
            ->whereNotIn('nombre', $excluidos)
            ->pluck('id');

        foreach ($permisosAdmin as $permisoId) {
            DB::table('rol_permisos')->insert([
                'rol_id' => 2,
                'permiso_id' => $permisoId,
            ]);
        }

        // ADMINISTRADOR_FOTOCHECK
        $permisosOperador = [
            'dashboard_ver', 'trabajadores_ver', 'trabajadores_crear',
            'trabajadores_editar', 'fotochecks_ver', 'fotochecks_generar',
            'fotochecks_reimprimir', 'estudiantes_ver', 'estudiantes_crear',
            'estudiantes_editar',
        ];

        $idsOperador = DB::table('permisos')
            ->whereIn('nombre', $permisosOperador)
            ->pluck('id');

        foreach ($idsOperador as $permisoId) {
            DB::table('rol_permisos')->insert([
                'rol_id' => 3,
                'permiso_id' => $permisoId,
            ]);
        }

        // CONSULTOR_FOTOCHECK
        $permisosConsultor = [
            'dashboard_ver', 'trabajadores_ver', 'fotochecks_ver', 'estudiantes_ver',
        ];

        $idsConsultor = DB::table('permisos')
            ->whereIn('nombre', $permisosConsultor)
            ->pluck('id');

        foreach ($idsConsultor as $permisoId) {
            DB::table('rol_permisos')->insert([
                'rol_id' => 4,
                'permiso_id' => $permisoId,
            ]);
        }

        // ADMINISTRADOR_SOLICITUD (rol_id = 5)
        $permisosAdminSolicitud = [
            'dashboard_ver',
            'solicitudes_ver', 'solicitudes_crear', 'solicitudes_editar', 'solicitudes_eliminar',
            'oficinas_ver', 'oficinas_crear', 'oficinas_editar', 'oficinas_eliminar',
            'tipo_solicitudes_ver', 'tipo_solicitudes_crear', 'tipo_solicitudes_editar', 'tipo_solicitudes_eliminar',
        ];

        $idsAdminSolicitud = DB::table('permisos')
            ->whereIn('nombre', $permisosAdminSolicitud)
            ->pluck('id');

        foreach ($idsAdminSolicitud as $permisoId) {
            DB::table('rol_permisos')->insert([
                'rol_id' => 5,
                'permiso_id' => $permisoId,
            ]);
        }

        // CONSULTOR_SOLICITUD (rol_id = 6)
        $permisosConsSolicitud = [
            'dashboard_ver',
            'solicitudes_ver',
            'oficinas_ver',
            'tipo_solicitudes_ver',
        ];

        $idsConsSolicitud = DB::table('permisos')
            ->whereIn('nombre', $permisosConsSolicitud)
            ->pluck('id');

        foreach ($idsConsSolicitud as $permisoId) {
            DB::table('rol_permisos')->insert([
                'rol_id' => 6,
                'permiso_id' => $permisoId,
            ]);
        }
    }
}
