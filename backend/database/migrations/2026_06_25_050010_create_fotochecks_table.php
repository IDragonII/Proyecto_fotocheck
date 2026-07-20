<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fotochecks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trabajador_id')->constrained('trabajadores')->cascadeOnDelete();
            $table->string('codigo', 50)->unique()->nullable();
            $table->string('url_qr');
            $table->string('qr_imagen')->nullable();
            $table->dateTime('fecha_emision')->useCurrent();
            $table->date('fecha_vencimiento')->nullable();
            $table->enum('estado', ['VIGENTE', 'VENCIDO', 'ANULADO'])->default('VIGENTE');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fotochecks');
    }
};
