<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->unsignedBigInteger('persona_id')->nullable()->change();
            $table->foreign('persona_id')->references('id')->on('personas')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->dropForeign(['persona_id']);
            $table->unsignedBigInteger('persona_id')->nullable(false)->change();
            $table->foreign('persona_id')->references('id')->on('personas')->cascadeOnDelete();
        });
    }
};
