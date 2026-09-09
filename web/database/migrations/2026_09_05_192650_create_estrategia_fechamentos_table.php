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
        Schema::create('estrategia_fechamentos', function (Blueprint $table) {
            $table->id();
            $table->integer('quantidade_jogos')->default(0);
            $table->json('dezenas')->nullable();
            $table->json('grupos')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estrategia_fechamentos');
    }
};
