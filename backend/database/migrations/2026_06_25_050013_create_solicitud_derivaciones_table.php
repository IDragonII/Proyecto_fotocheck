<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitud_derivaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('solicitudes')->cascadeOnDelete();
            $table->foreignId('oficina_origen_id')->nullable()->constrained('oficinas')->nullOnDelete();
            $table->foreignId('oficina_destino_id')->constrained('oficinas')->cascadeOnDelete();
            $table->foreignId('derivado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->text('motivo')->nullable();
            $table->timestamp('fecha')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_derivaciones');
    }
};
