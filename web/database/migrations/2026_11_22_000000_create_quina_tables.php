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
        Schema::create('quina_boloes', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->integer('status')->default(0);
            $table->integer('concurso_alvo')->nullable();
            $table->integer('total_numeros')->default(5);
            $table->integer('max_quadrante_1')->default(3);
            $table->integer('max_quadrante_2')->default(3);
            $table->integer('max_quadrante_3')->default(3);
            $table->integer('max_quadrante_4')->default(3);
            $table->integer('max_linha')->default(3);
            $table->integer('max_coluna')->default(3);
            $table->decimal('valor_cota', 10, 2)->default(0.00);
            $table->decimal('valor_total', 12, 2)->default(0.00);
            $table->integer('dezenas_por_participante')->default(0);
            $table->timestamps();
        });

        Schema::create('quina_bolao_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quina_bolao_id')->constrained('quina_boloes')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->json('numeros_selecionados')->nullable();
            $table->timestamps();
        });

        Schema::create('quina_fechamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bolao_id')->constrained('quina_boloes')->onDelete('cascade');
            $table->integer('quantidade_jogos')->default(0);
            $table->json('dezenas')->nullable();
            $table->json('grupos')->nullable();
            $table->json('resultado')->nullable();
            $table->timestamps();
        });

        Schema::create('quina_fechamento_jogos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fechamento_id')->constrained('quina_fechamentos')->onDelete('cascade');
            $table->json('dezenas');
            $table->string('grupos')->nullable();
            $table->integer('status')->default(0);
            $table->integer('acertos')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quina_fechamento_jogos');
        Schema::dropIfExists('quina_fechamentos');
        Schema::dropIfExists('quina_bolao_user');
        Schema::dropIfExists('quina_boloes');
    }
};
