<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->enum('motivo_solicitud', ['CREACION', 'RENOVACION', 'MODIFICACION', 'BAJA'])
                ->nullable()
                ->after('oficina_actual_id');
            $table->string('tipo_cuenta', 255)->nullable()->after('motivo_solicitud');
            $table->string('sistema_especifico', 255)->nullable()->after('tipo_cuenta');
            $table->boolean('usuario_creado')->nullable()->after('sistema_especifico');
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->dropColumn([
                'motivo_solicitud', 'tipo_cuenta', 'sistema_especifico', 'usuario_creado',
            ]);
        });
    }
};
