<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('usuario', 50)->unique();
            $table->string('clave', 255);
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->enum('estado', ['ACTIVO', 'INACTIVO'])->default('ACTIVO');
            $table->datetime('ultimo_acceso')->nullable();
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->integer('intentos_fallidos')->default(0);
            $table->datetime('bloqueado_hasta')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
