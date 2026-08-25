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
        Schema::table('lotofacil_bolao_user', function (Blueprint $table) {
            $table->json('numeros_selecionados')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lotofacil_bolao_user', function (Blueprint $table) {
            $table->dropColumn('numeros_selecionados');
        });
    }
};
