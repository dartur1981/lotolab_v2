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
        if (!Schema::hasColumn('quina_boloes', 'max_sub_quadrante')) {
            Schema::table('quina_boloes', function (Blueprint $table) {
                $table->integer('max_sub_quadrante')->default(1)->after('max_quadrante_4');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('quina_boloes', 'max_sub_quadrante')) {
            Schema::table('quina_boloes', function (Blueprint $table) {
                $table->dropColumn('max_sub_quadrante');
            });
        }
    }
};
