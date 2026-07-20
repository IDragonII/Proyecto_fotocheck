<?php

namespace Database\Seeders;

use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Database\Seeder;

class ApiKeyPermisoSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [
            ['nombre' => 'api_keys_ver', 'descripcion' => 'Ver claves API', 'es_critico' => true],
            ['nombre' => 'api_keys_crear', 'descripcion' => 'Crear claves API', 'es_critico' => true],
            ['nombre' => 'api_keys_editar', 'descripcion' => 'Editar claves API', 'es_critico' => true],
            ['nombre' => 'api_keys_eliminar', 'descripcion' => 'Eliminar claves API', 'es_critico' => true],
            ['nombre' => 'oficinas_ver', 'descripcion' => 'Ver oficinas', 'es_critico' => false],
            ['nombre' => 'oficinas_crear', 'descripcion' => 'Crear oficinas', 'es_critico' => false],
            ['nombre' => 'oficinas_editar', 'descripcion' => 'Editar oficinas', 'es_critico' => false],
            ['nombre' => 'oficinas_eliminar', 'descripcion' => 'Eliminar oficinas', 'es_critico' => false],
            ['nombre' => 'tipo_solicitudes_ver', 'descripcion' => 'Ver tipos de solicitud', 'es_critico' => false],
            ['nombre' => 'tipo_solicitudes_crear', 'descripcion' => 'Crear tipos de solicitud', 'es_critico' => false],
            ['nombre' => 'tipo_solicitudes_editar', 'descripcion' => 'Editar tipos de solicitud', 'es_critico' => false],
            ['nombre' => 'tipo_solicitudes_eliminar', 'descripcion' => 'Eliminar tipos de solicitud', 'es_critico' => false],
        ];

        $permisoIds = [];
        foreach ($permisos as $permiso) {
            $p = Permiso::updateOrCreate(
                ['nombre' => $permiso['nombre']],
                $permiso
            );
            $permisoIds[] = $p->id;
        }

        $rolesConAcceso = ['SUPER_ADMIN', 'ADMIN'];
        foreach ($rolesConAcceso as $nombreRol) {
            $rol = Rol::where('nombre', $nombreRol)->first();
            if ($rol) {
                $rol->permisos()->syncWithoutDetaching($permisoIds);
            }
        }
    }
}
