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
        Schema::create('estrategia_fechamento_jogos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estrategia_fechamento_id')->constrained('estrategia_fechamentos')->cascadeOnDelete();
            $table->json('dezenas')->nullable();
            $table->integer('score')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estrategia_fechamento_jogos');
    }
};
