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
        Schema::table('lotofacil_fechamentos', function (Blueprint $table) {
            $table->json('dezenas')->nullable()->after('quantidade_jogos');
            $table->json('resultado')->nullable()->after('dezenas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lotofacil_fechamentos', function (Blueprint $table) {
            $table->dropColumn(['dezenas', 'resultado']);
        });
    }
};
