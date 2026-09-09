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
        Schema::table('estrategia_fechamento_jogos', function (Blueprint $table) {
            $table->string('status')->default('0')->comment('0=Pendente, 1=Divergente, 2=Ignorado, 3=Lancado, 4=Apostado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estrategia_fechamento_jogos', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
