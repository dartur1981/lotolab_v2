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
        Schema::table('megasena_boloes', function (Blueprint $table) {
            if (Schema::hasColumn('megasena_boloes', 'max_quadrante_4')) {
                $table->dropColumn('max_quadrante_4');
            }
            if (!Schema::hasColumn('megasena_boloes', 'max_sub_quadrante')) {
                $table->integer('max_sub_quadrante')->default(1)->after('max_quadrante_3');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('megasena_boloes', function (Blueprint $table) {
            $table->integer('max_quadrante_4')->default(3)->after('max_quadrante_3');
            if (Schema::hasColumn('megasena_boloes', 'max_sub_quadrante')) {
                $table->dropColumn('max_sub_quadrante');
            }
        });
    }
};
