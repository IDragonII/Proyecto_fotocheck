<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accesos_qr', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trabajador_id')->constrained('trabajadores')->cascadeOnDelete();
            $table->string('ip', 45)->nullable();
            $table->text('navegador')->nullable();
            $table->dateTime('fecha_acceso')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accesos_qr');
    }
};
