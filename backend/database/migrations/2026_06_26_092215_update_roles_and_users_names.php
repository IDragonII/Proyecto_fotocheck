<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Actualizar nombres de roles
        DB::table('roles')->where('nombre', 'OPERADOR')->update(['nombre' => 'ADMINISTRADOR_FOTOCHECK']);
        DB::table('roles')->where('nombre', 'CONSULTOR')->update(['nombre' => 'CONSULTOR_FOTOCHECK']);

        // Actualizar nombres de usuarios
        DB::table('usuarios')->where('usuario', 'admin.una')->update(['usuario' => 'SUPER_ADMIN.UNAP']);
        DB::table('usuarios')->where('usuario', 'rrhh.una')->update(['usuario' => 'ADMIN.UNAP']);
        DB::table('usuarios')->where('usuario', 'ti.una')->update(['usuario' => 'ADMINISTRADOR_FOTOCHECK.UNAP']);
        DB::table('usuarios')->where('usuario', 'consultor.una')->update(['usuario' => 'CONSULTOR_FOTOCHECK.UNAP']);
    }

    public function down(): void
    {
        // Revertir nombres de roles
        DB::table('roles')->where('nombre', 'ADMINISTRADOR_FOTOCHECK')->update(['nombre' => 'OPERADOR']);
        DB::table('roles')->where('nombre', 'CONSULTOR_FOTOCHECK')->update(['nombre' => 'CONSULTOR']);

        // Revertir nombres de usuarios
        DB::table('usuarios')->where('usuario', 'SUPER_ADMIN.UNAP')->update(['usuario' => 'admin.una']);
        DB::table('usuarios')->where('usuario', 'ADMIN.UNAP')->update(['usuario' => 'rrhh.una']);
        DB::table('usuarios')->where('usuario', 'ADMINISTRADOR_FOTOCHECK.UNAP')->update(['usuario' => 'ti.una']);
        DB::table('usuarios')->where('usuario', 'CONSULTOR_FOTOCHECK.UNAP')->update(['usuario' => 'consultor.una']);
    }
};
