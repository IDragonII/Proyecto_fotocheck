<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')->constrained('personas')->cascadeOnDelete();
            $table->enum('tipo_solicitud', [
                'CORREO', 'CONTRASEÑA', 'ACTIVACION', 'AULA_VIRTUAL', 'FIRMA_DIGITAL', 'DOMINIO',
            ]);
            $table->enum('estado', ['PENDIENTE', 'EN_PROCESO', 'RESUELTO', 'RECHAZADO'])->default('PENDIENTE');
            $table->foreignId('oficina_actual_id')->nullable()->constrained('oficinas')->nullOnDelete();
            $table->text('adjuntos')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('atendido_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('fecha_solicitud')->useCurrent();
            $table->datetime('fecha_atencion')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes');
    }
};
