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
        Schema::table('personas', function (Blueprint $table) {
            $table->string('url_foto_presencial')->nullable()->after('foto');
            $table->string('url_foto_virtual')->nullable()->after('url_foto_presencial');
        });
    }

    public function down(): void
    {
        Schema::table('personas', function (Blueprint $table) {
            $table->dropColumn(['url_foto_presencial', 'url_foto_virtual']);
        });
    }
};
