<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lotofacil_fechamento_jogos', function (Blueprint $table) {
            $table->integer('status')->default(0)->after('dezenas');
        });
    }

    public function down(): void
    {
        Schema::table('lotofacil_fechamento_jogos', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
