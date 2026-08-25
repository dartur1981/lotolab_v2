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
        Schema::create('lotofacil_estatisticas_trincas', function (Blueprint $table) {
            $table->id();
            $table->string('dezenas', 10)->unique(); // Ex: "1,3,15"
            $table->integer('frequencia')->default(0);
            $table->decimal('percentual', 5, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lotofacil_estatisticas_trincas');
    }
};
