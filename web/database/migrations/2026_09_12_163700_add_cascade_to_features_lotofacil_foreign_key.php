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
        $schema = Schema::connection('analytics_lotofacil');

        if ($schema->hasTable('features_lotofacil')) {
            try {
                DB::connection('analytics_lotofacil')->statement('ALTER TABLE features_lotofacil DROP FOREIGN KEY features_lotofacil_ibfk_1');
            } catch (\Throwable $e) {
                // Ignore if it doesn't exist
            }

            try {
                DB::connection('analytics_lotofacil')->statement('ALTER TABLE features_lotofacil ADD CONSTRAINT features_lotofacil_ibfk_1 FOREIGN KEY (concurso) REFERENCES resultados_lotofacil (concurso) ON DELETE CASCADE');
            } catch (\Throwable $e) {
                //
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $schema = Schema::connection('analytics_lotofacil');

        if ($schema->hasTable('features_lotofacil')) {
            try {
                DB::connection('analytics_lotofacil')->statement('ALTER TABLE features_lotofacil DROP FOREIGN KEY features_lotofacil_ibfk_1');
                DB::connection('analytics_lotofacil')->statement('ALTER TABLE features_lotofacil ADD CONSTRAINT features_lotofacil_ibfk_1 FOREIGN KEY (concurso) REFERENCES resultados_lotofacil (concurso)');
            } catch (\Throwable $e) {
                //
            }
        }
    }
};