<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CalcularScoresLotofacil extends Command
{
    protected $signature = 'lotofacil:calcular-scores {--force : Recalcular mesmo os que já têm score}';

    protected $description = 'Recalcula o score e repetidas para todas as combinações de 18 números';

    public function handle()
    {
        $this->info('Iniciando o cálculo de scores...');

        $service = new \App\Services\ScoreLotofacilService();

        $query = \App\Models\Lotofacil\Combinacao18::query();

        if (!$this->option('force')) {
            $query->whereNull('score');
        }

        $total = $query->count();
        $this->info("Total a calcular: {$total} combinações");

        if ($total === 0) {
            $this->info('Nenhuma combinação a calcular. Use --force para recalcular todas.');
            return;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunk(200, function ($combinacoes) use ($service, $bar) {
            foreach ($combinacoes as $record) {
                $service->calcularESalvar($record);
                $bar->advance();
            }
            // Libera memória entre chunks
            gc_collect_cycles();
        });

        $bar->finish();
        $this->newLine();
        $this->info('Cálculo concluído com sucesso!');
    }
}
