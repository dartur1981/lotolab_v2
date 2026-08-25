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
        Schema::create('lotofacil_fechamento_jogos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fechamento_id')->constrained('lotofacil_fechamentos')->onDelete('cascade');
            $table->json('dezenas');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lotofacil_fechamento_jogos');
    }
};
