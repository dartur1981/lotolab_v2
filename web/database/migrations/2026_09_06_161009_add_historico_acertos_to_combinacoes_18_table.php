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
        $schema = Schema::connection('analytics_lotofacil');

        if (!$schema->hasTable('combinacoes_18')) {
            return;
        }

        $schema->table('combinacoes_18', function (Blueprint $table) use ($schema) {
            if (!$schema->hasColumn('combinacoes_18', 'historico_15')) {
                $table->integer('historico_15')->default(0)->after('acertos_11');
            }
            if (!$schema->hasColumn('combinacoes_18', 'historico_14')) {
                $table->integer('historico_14')->default(0)->after('historico_15');
            }
            if (!$schema->hasColumn('combinacoes_18', 'historico_13')) {
                $table->integer('historico_13')->default(0)->after('historico_14');
            }
            if (!$schema->hasColumn('combinacoes_18', 'historico_12')) {
                $table->integer('historico_12')->default(0)->after('historico_13');
            }
            if (!$schema->hasColumn('combinacoes_18', 'historico_11')) {
                $table->integer('historico_11')->default(0)->after('historico_12');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $schema = Schema::connection('analytics_lotofacil');
        if ($schema->hasTable('combinacoes_18')) {
            $schema->table('combinacoes_18', function (Blueprint $table) use ($schema) {
                $cols = array_filter(['historico_15', 'historico_14', 'historico_13', 'historico_12', 'historico_11'], fn($c) => $schema->hasColumn('combinacoes_18', $c));
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
