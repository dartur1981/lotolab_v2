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
        Schema::connection('analytics_lotofacil')->table('combinacoes_18', function (Blueprint $table) {
            $table->integer('score')->nullable()->after('soma');
            $table->integer('repetidas_ant1')->nullable()->after('score');
            $table->integer('repetidas_ant2')->nullable()->after('repetidas_ant1');
            $table->integer('repetidas_ant3')->nullable()->after('repetidas_ant2');
        });
    }

    public function down(): void
    {
        Schema::connection('analytics_lotofacil')->table('combinacoes_18', function (Blueprint $table) {
            $table->dropColumn(['score', 'repetidas_ant1', 'repetidas_ant2', 'repetidas_ant3']);
        });
    }
};
