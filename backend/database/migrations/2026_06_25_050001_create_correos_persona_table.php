<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('correos_persona', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')->constrained('personas')->cascadeOnDelete();
            $table->string('correo', 150);
            $table->enum('tipo', ['PERSONAL', 'INSTITUCIONAL', 'ALTERNATIVO'])->default('PERSONAL');
            $table->boolean('principal')->default(false);
            $table->enum('estado', ['ACTIVO', 'INACTIVO'])->default('ACTIVO');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correos_persona');
    }
};
