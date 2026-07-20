<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermisosSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('permisos')->insert([
            // Dashboard
            ['nombre' => 'dashboard_ver',         'descripcion' => 'Ver dashboard',                'es_critico' => 0],

            // Trabajadores
            ['nombre' => 'trabajadores_ver',       'descripcion' => 'Ver trabajadores',             'es_critico' => 0],
            ['nombre' => 'trabajadores_crear',     'descripcion' => 'Registrar trabajadores',       'es_critico' => 0],
            ['nombre' => 'trabajadores_editar',    'descripcion' => 'Editar trabajadores',          'es_critico' => 0],
            ['nombre' => 'trabajadores_eliminar',  'descripcion' => 'Eliminar trabajadores',        'es_critico' => 0],

            // Estudiantes
            ['nombre' => 'estudiantes_ver',        'descripcion' => 'Ver estudiantes',              'es_critico' => 0],
            ['nombre' => 'estudiantes_crear',      'descripcion' => 'Registrar estudiantes',        'es_critico' => 0],
            ['nombre' => 'estudiantes_editar',     'descripcion' => 'Editar estudiantes',           'es_critico' => 0],
            ['nombre' => 'estudiantes_eliminar',   'descripcion' => 'Eliminar estudiantes',         'es_critico' => 0],

            // Fotochecks
            ['nombre' => 'fotochecks_ver',         'descripcion' => 'Ver fotochecks',               'es_critico' => 0],
            ['nombre' => 'fotochecks_generar',     'descripcion' => 'Generar fotochecks',           'es_critico' => 0],
            ['nombre' => 'fotochecks_reimprimir',  'descripcion' => 'Reimprimir fotochecks',        'es_critico' => 0],
            ['nombre' => 'fotochecks_anular',      'descripcion' => 'Anular fotochecks',            'es_critico' => 0],

            // Usuarios
            ['nombre' => 'usuarios_ver',           'descripcion' => 'Ver usuarios',                 'es_critico' => 0],
            ['nombre' => 'usuarios_crear',         'descripcion' => 'Crear usuarios',               'es_critico' => 0],
            ['nombre' => 'usuarios_editar',        'descripcion' => 'Editar usuarios',              'es_critico' => 0],
            ['nombre' => 'usuarios_eliminar',      'descripcion' => 'Eliminar usuarios',            'es_critico' => 0],

            // Roles
            ['nombre' => 'roles_ver',              'descripcion' => 'Ver roles',                    'es_critico' => 1],
            ['nombre' => 'roles_crear',            'descripcion' => 'Crear roles',                  'es_critico' => 1],
            ['nombre' => 'roles_editar',           'descripcion' => 'Editar roles',                 'es_critico' => 1],
            ['nombre' => 'roles_eliminar',         'descripcion' => 'Eliminar roles',               'es_critico' => 1],

            // Permisos
            ['nombre' => 'permisos_ver',           'descripcion' => 'Ver permisos',                 'es_critico' => 1],
            ['nombre' => 'permisos_crear',         'descripcion' => 'Crear permisos',               'es_critico' => 1],
            ['nombre' => 'permisos_editar',        'descripcion' => 'Editar permisos',              'es_critico' => 1],
            ['nombre' => 'permisos_eliminar',      'descripcion' => 'Eliminar permisos',            'es_critico' => 1],
            ['nombre' => 'permisos_asignar',       'descripcion' => 'Asignar permisos',             'es_critico' => 1],

            // Logs
            ['nombre' => 'logs_ver',               'descripcion' => 'Ver logs',                     'es_critico' => 0],

            // Configuracion
            ['nombre' => 'configuracion_ver',      'descripcion' => 'Ver configuracion',            'es_critico' => 0],
            ['nombre' => 'configuracion_editar',   'descripcion' => 'Editar configuracion',         'es_critico' => 1],

            // Solicitudes
            ['nombre' => 'solicitudes_ver',        'descripcion' => 'Ver solicitudes/tickets',      'es_critico' => 0],
            ['nombre' => 'solicitudes_crear',      'descripcion' => 'Crear solicitudes/tickets',    'es_critico' => 0],
            ['nombre' => 'solicitudes_editar',     'descripcion' => 'Editar solicitudes/tickets',   'es_critico' => 0],
            ['nombre' => 'solicitudes_eliminar',   'descripcion' => 'Eliminar solicitudes/tickets', 'es_critico' => 0],
        ]);
    }
}
