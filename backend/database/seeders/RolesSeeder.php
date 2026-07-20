<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->insert([
            [
                'nombre' => 'SUPER_ADMIN',
                'descripcion' => 'Acceso total al sistema',
                'nivel' => 100,
                'estado' => 'ACTIVO',
            ],
            [
                'nombre' => 'ADMIN',
                'descripcion' => 'Administracion general',
                'nivel' => 80,
                'estado' => 'ACTIVO',
            ],
            [
                'nombre' => 'ADMINISTRADOR_FOTOCHECK',
                'descripcion' => 'Gestion de trabajadores y fotochecks',
                'nivel' => 50,
                'estado' => 'ACTIVO',
            ],
            [
                'nombre' => 'CONSULTOR_FOTOCHECK',
                'descripcion' => 'Solo consulta de informacion',
                'nivel' => 10,
                'estado' => 'ACTIVO',
            ],
            [
                'nombre' => 'ADMINISTRADOR_SOLICITUD',
                'descripcion' => 'Gestion completa de solicitudes/tickets',
                'nivel' => 50,
                'estado' => 'ACTIVO',
            ],
            [
                'nombre' => 'CONSULTOR_SOLICITUD',
                'descripcion' => 'Solo consulta de solicitudes/tickets',
                'nivel' => 10,
                'estado' => 'ACTIVO',
            ],
        ]);
    }
}
