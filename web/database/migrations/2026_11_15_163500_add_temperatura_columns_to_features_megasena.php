<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $schema = Schema::connection('analytics_megasena');

        if ($schema->hasTable('features_megasena')) {
            $schema->table('features_megasena', function (Blueprint $table) use ($schema) {
                if (!$schema->hasColumn('features_megasena', 'temp_quente')) {
                    $table->integer('temp_quente')->default(0)->after('soma');
                }
                if (!$schema->hasColumn('features_megasena', 'temp_morno')) {
                    $table->integer('temp_morno')->default(0)->after('temp_quente');
                }
                if (!$schema->hasColumn('features_megasena', 'temp_frio')) {
                    $table->integer('temp_frio')->default(0)->after('temp_morno');
                }
            });
        }

        if ($schema->hasTable('tendencias_dezenas_megasena')) {
            $schema->table('tendencias_dezenas_megasena', function (Blueprint $table) use ($schema) {
                if (!$schema->hasColumn('tendencias_dezenas_megasena', 'score_temperatura')) {
                    $table->float('score_temperatura')->default(0.0)->after('atraso');
                }
                if (!$schema->hasColumn('tendencias_dezenas_megasena', 'r_10')) {
                    $table->float('r_10')->default(0.0)->after('score_temperatura');
                }
                if (!$schema->hasColumn('tendencias_dezenas_megasena', 'r_20')) {
                    $table->float('r_20')->default(0.0)->after('r_10');
                }
                if (!$schema->hasColumn('tendencias_dezenas_megasena', 'r_50')) {
                    $table->float('r_50')->default(0.0)->after('r_20');
                }
                if (!$schema->hasColumn('tendencias_dezenas_megasena', 'r_100')) {
                    $table->float('r_100')->default(0.0)->after('r_50');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $schema = Schema::connection('analytics_megasena');

        if ($schema->hasTable('features_megasena')) {
            $schema->table('features_megasena', function (Blueprint $table) use ($schema) {
                $columns = array_filter(['temp_quente', 'temp_morno', 'temp_frio'], fn($col) => $schema->hasColumn('features_megasena', $col));
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }

        if ($schema->hasTable('tendencias_dezenas_megasena')) {
            $schema->table('tendencias_dezenas_megasena', function (Blueprint $table) use ($schema) {
                $columns = array_filter(['score_temperatura', 'r_10', 'r_20', 'r_50', 'r_100'], fn($col) => $schema->hasColumn('tendencias_dezenas_megasena', $col));
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
