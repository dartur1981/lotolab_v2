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
        Schema::table('megasena_fechamentos', function (Blueprint $table) {
            if (!Schema::hasColumn('megasena_fechamentos', 'grupos')) {
                $table->json('grupos')->nullable()->after('resultado');
            }
        });

        Schema::table('megasena_fechamento_jogos', function (Blueprint $table) {
            if (!Schema::hasColumn('megasena_fechamento_jogos', 'grupos')) {
                $table->string('grupos', 50)->nullable()->after('dezenas');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('megasena_fechamentos', function (Blueprint $table) {
            if (Schema::hasColumn('megasena_fechamentos', 'grupos')) {
                $table->dropColumn('grupos');
            }
        });

        Schema::table('megasena_fechamento_jogos', function (Blueprint $table) {
            if (Schema::hasColumn('megasena_fechamento_jogos', 'grupos')) {
                $table->dropColumn('grupos');
            }
        });
    }
};
