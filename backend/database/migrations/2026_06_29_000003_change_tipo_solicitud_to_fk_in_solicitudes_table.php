<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            if (Schema::hasColumn('solicitudes', 'tipo_solicitud')) {
                $table->dropColumn('tipo_solicitud');
            }
        });

        if (! Schema::hasColumn('solicitudes', 'tipo_solicitud_id')) {
            Schema::table('solicitudes', function (Blueprint $table) {
                $table->foreignId('tipo_solicitud_id')->after('persona_id')->constrained('tipo_solicitudes')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->dropForeign(['tipo_solicitud_id']);
            $table->dropColumn('tipo_solicitud_id');
        });

        Schema::table('solicitudes', function (Blueprint $table) {
            $table->enum('tipo_solicitud', [
                'CORREO', 'CONTRASEÑA', 'ACTIVACION', 'AULA_VIRTUAL', 'FIRMA_DIGITAL', 'DOMINIO',
            ])->after('persona_id');
        });
    }
};
