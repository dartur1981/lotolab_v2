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
        $schema = Schema::connection('analytics_megasena');

        if ($schema->hasTable('features_megasena')) {
            $schema->table('features_megasena', function (Blueprint $table) use ($schema) {
                for ($i = 1; $i <= 15; $i++) {
                    $col = "sub_{$i}";
                    if (!$schema->hasColumn('features_megasena', $col)) {
                        $table->integer($col)->default(0)->nullable();
                    }
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
                $colsToDrop = [];
                for ($i = 1; $i <= 15; $i++) {
                    $col = "sub_{$i}";
                    if ($schema->hasColumn('features_megasena', $col)) {
                        $colsToDrop[] = $col;
                    }
                }
                if (!empty($colsToDrop)) {
                    $table->dropColumn($colsToDrop);
                }
            });
        }
    }
};
