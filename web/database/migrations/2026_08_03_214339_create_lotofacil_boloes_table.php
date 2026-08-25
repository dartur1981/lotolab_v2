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
        Schema::create('lotofacil_boloes', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->integer('concurso_alvo')->nullable();
            $table->integer('total_numeros')->default(15);
            $table->integer('max_moldura')->default(11);
            $table->integer('max_miolo')->default(6);
            $table->integer('max_linha')->default(4);
            $table->integer('max_coluna')->default(4);
            $table->decimal('valor_cota', 10, 2)->default(0.00);
            $table->decimal('valor_total', 12, 2)->default(0.00);
            $table->integer('dezenas_por_participante')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lotofacil_boloes');
    }
};
