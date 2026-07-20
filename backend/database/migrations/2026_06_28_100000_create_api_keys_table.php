<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->string('clave_hash', 64)->unique();
            $table->string('clave_prefijo', 8);
            $table->json('permisos');
            $table->integer('rate_limit')->default(600);
            $table->enum('estado', ['ACTIVO', 'INACTIVO'])->default('ACTIVO');
            $table->datetime('expira_en')->nullable();
            $table->datetime('ultimo_uso')->nullable();
            $table->unsignedBigInteger('total_usos')->default(0);
            $table->timestamps();

            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
