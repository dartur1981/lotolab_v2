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
        Schema::create('lotofacil_estatisticas_padroes', function (Blueprint $table) {
            $table->id();
            $table->string('padrao', 50)->unique(); // Ex: "7 pares / 8 impares"
            $table->integer('valor')->default(0); // Frequencia ou outro valor
            $table->integer('atraso')->default(0); // Há quantos sorteios não aparece
            $table->decimal('percentual', 5, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lotofacil_estatisticas_padroes');
    }
};
