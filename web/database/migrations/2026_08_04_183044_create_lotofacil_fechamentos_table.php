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
        Schema::create('lotofacil_fechamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bolao_id')->constrained('lotofacil_boloes')->onDelete('cascade');
            $table->integer('quantidade_jogos')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lotofacil_fechamentos');
    }
};
