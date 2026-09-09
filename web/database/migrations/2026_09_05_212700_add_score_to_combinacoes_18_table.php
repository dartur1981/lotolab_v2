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
            $schema->create('combinacoes_18', function (Blueprint $table) {
                $table->id();
                $table->string('dezenas', 100)->unique();
                $table->integer('pares')->nullable();
                $table->integer('impares')->nullable();
                $table->integer('primos')->nullable();
                $table->integer('fibonacci')->nullable();
                $table->integer('moldura')->nullable();
                $table->integer('miolo')->nullable();
                $table->integer('soma')->nullable();
                $table->integer('acertos_15')->default(0);
                $table->integer('acertos_14')->default(0);
                $table->integer('acertos_13')->default(0);
                $table->integer('acertos_12')->default(0);
                $table->integer('acertos_11')->default(0);
                $table->integer('score')->nullable();
                $table->integer('repetidas_ant1')->nullable();
                $table->integer('repetidas_ant2')->nullable();
                $table->integer('repetidas_ant3')->nullable();
                $table->timestamps();
            });
            return;
        }

        $schema->table('combinacoes_18', function (Blueprint $table) use ($schema) {
            if (!$schema->hasColumn('combinacoes_18', 'score')) {
                $table->integer('score')->nullable()->after('soma');
            }
            if (!$schema->hasColumn('combinacoes_18', 'repetidas_ant1')) {
                $table->integer('repetidas_ant1')->nullable()->after('score');
            }
            if (!$schema->hasColumn('combinacoes_18', 'repetidas_ant2')) {
                $table->integer('repetidas_ant2')->nullable()->after('repetidas_ant1');
            }
            if (!$schema->hasColumn('combinacoes_18', 'repetidas_ant3')) {
                $table->integer('repetidas_ant3')->nullable()->after('repetidas_ant2');
            }
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('analytics_lotofacil');
        if ($schema->hasTable('combinacoes_18')) {
            $schema->table('combinacoes_18', function (Blueprint $table) use ($schema) {
                $cols = array_filter(['score', 'repetidas_ant1', 'repetidas_ant2', 'repetidas_ant3'], fn($c) => $schema->hasColumn('combinacoes_18', $c));
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
