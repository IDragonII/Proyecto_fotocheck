<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuarioSuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('usuario_roles')->truncate();
        DB::table('usuarios')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $users = [
            ['usuario' => 'SUPER_ADMIN.UNAP',          'clave' => 'Un@Super!2026#Seg',     'rol_id' => 1, 'oficina_id' => null],
            ['usuario' => 'ADMIN.UNAP',                'clave' => 'Un@Adm!n2026#Seg',     'rol_id' => 2, 'oficina_id' => null],
            ['usuario' => 'ADMINISTRADOR_FOTOCHECK.UNAP', 'clave' => 'F0t0ch3ck!2026$',   'rol_id' => 3, 'oficina_id' => null],
            ['usuario' => 'CONSULTOR_FOTOCHECK.UNAP',  'clave' => 'C0nsult0r!2026#',      'rol_id' => 4, 'oficina_id' => null],
            ['usuario' => 'ADMINISTRADOR_SOLICITUD.UNAP', 'clave' => 'T1ck3t!2026$Adm',  'rol_id' => 5, 'oficina_id' => null],
            ['usuario' => 'CONSULTOR_SOLICITUD.UNAP',  'clave' => 'T1ck3t!2026$C0ns',    'rol_id' => 6, 'oficina_id' => null],
        ];

        foreach ($users as $u) {
            $id = DB::table('usuarios')->insertGetId([
                'usuario' => $u['usuario'],
                'clave' => Hash::make($u['clave']),
                'nombres' => $u['nombres'] ?? $u['usuario'],
                'apellidos' => $u['apellidos'] ?? 'Universidad',
                'estado' => 'ACTIVO',
                'oficina_id' => $u['oficina_id'] ?? null,
            ]);
            DB::table('usuario_roles')->insert([
                'usuario_id' => $id,
                'rol_id' => $u['rol_id'],
            ]);
        }
    }
}
