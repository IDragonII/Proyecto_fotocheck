<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuario_permisos', function (Blueprint $table) {
            $table->enum('tipo', ['extra', 'negado'])->default('extra')->after('permiso_id');
        });
    }

    public function down(): void
    {
        Schema::table('usuario_permisos', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
};
