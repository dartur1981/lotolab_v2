<?php

namespace App\Filament\Exports;

use App\Models\Lotofacil\Combinacao18;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Str;

class Combinacao18Exporter extends Exporter
{
    protected static ?string $model = Combinacao18::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('dezenas')
                ->label('Dezenas (18)')
                ->formatStateUsing(fn (string $state): string => str_replace(',', ' - ', $state)),
            ExportColumn::make('score')->label('Score'),
            ExportColumn::make('repetidas_ant1')->label('Rep. Conc. -1'),
            ExportColumn::make('repetidas_ant2')->label('Rep. Conc. -2'),
            ExportColumn::make('repetidas_ant3')->label('Rep. Conc. -3'),
            ExportColumn::make('pares'),
            ExportColumn::make('impares'),
            ExportColumn::make('primos'),
            ExportColumn::make('fibonacci'),
            ExportColumn::make('moldura'),
            ExportColumn::make('miolo'),
            ExportColumn::make('soma'),
            ExportColumn::make('acertos_15')->label('Sim. 15 Pts'),
            ExportColumn::make('acertos_14')->label('Sim. 14 Pts'),
            ExportColumn::make('acertos_13')->label('Sim. 13 Pts'),
            ExportColumn::make('acertos_12')->label('Sim. 12 Pts'),
            ExportColumn::make('acertos_11')->label('Sim. 11 Pts'),
            ExportColumn::make('historico_15')->label('Hist. 15 Pts'),
            ExportColumn::make('historico_14')->label('Hist. 14 Pts'),
            ExportColumn::make('historico_13')->label('Hist. 13 Pts'),
            ExportColumn::make('historico_12')->label('Hist. 12 Pts'),
            ExportColumn::make('historico_11')->label('Hist. 11 Pts'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'A exportação de ' . number_format($export->successful_rows, 0, ',', '.') . ' combinações foi concluída.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount, 0, ',', '.') . ' falharam ao exportar.';
        }

        return $body;
    }

    public static function modifyQuery(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        // Ordena pelo ID primário para otimizar exportação em chunks e evitar timeouts.
        // Os filtros ativos na tabela são aplicados ANTES de chegar aqui pelo Filament,
        // portanto filtre na UI antes de exportar para reduzir o volume.
        return $query->reorder('id');
    }

    public static function getMaxRows(): ?int
    {
        // Limita a 50.000 linhas por exportação para evitar esgotamento de memória.
        // Use os filtros da tabela (Score, Acertos, etc.) antes de exportar.
        return 50000;
    }

    public static function getDefaultChunkSize(): int
    {
        return 500; // Chunks menores para usar menos memória por vez
    }

    public function getFormats(): array
    {
        return [
            \Filament\Actions\Exports\Enums\ExportFormat::Csv,
        ];
    }

    public static function getCsvDelimiter(): string
    {
        return ';';
    }
}
