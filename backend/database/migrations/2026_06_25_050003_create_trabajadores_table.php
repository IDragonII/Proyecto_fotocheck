<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trabajadores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('persona_id');
            $table->string('codigo_unico', 50)->unique();
            $table->string('codigo_nfs', 50)->nullable();
            $table->string('empresa', 200)->nullable();
            $table->string('area', 100)->nullable();
            $table->string('dependencia', 150)->nullable();
            $table->string('cargo', 100)->nullable();
            $table->string('regimen', 150)->nullable();
            $table->string('resolucion_rectoral', 100)->nullable();
            $table->string('vigencia', 100)->nullable();
            $table->date('fecha_emision')->nullable();
            $table->date('fecha_ingreso')->nullable();
            $table->timestamps();
            $table->foreign('persona_id')->references('id')->on('personas')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trabajadores');
    }
};
