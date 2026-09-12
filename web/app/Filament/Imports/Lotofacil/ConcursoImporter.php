<?php

namespace App\Filament\Imports\Lotofacil;

use App\Models\Lotofacil\Concurso;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class ConcursoImporter extends Importer
{
    protected static ?string $model = Concurso::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('concurso')
                ->label('Concurso / Número')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('data_sorteio')
                ->label('Data Sorteio')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('bola_1')->label('Bola 1')->numeric(),
            ImportColumn::make('bola_2')->label('Bola 2')->numeric(),
            ImportColumn::make('bola_3')->label('Bola 3')->numeric(),
            ImportColumn::make('bola_4')->label('Bola 4')->numeric(),
            ImportColumn::make('bola_5')->label('Bola 5')->numeric(),
            ImportColumn::make('bola_6')->label('Bola 6')->numeric(),
            ImportColumn::make('bola_7')->label('Bola 7')->numeric(),
            ImportColumn::make('bola_8')->label('Bola 8')->numeric(),
            ImportColumn::make('bola_9')->label('Bola 9')->numeric(),
            ImportColumn::make('bola_10')->label('Bola 10')->numeric(),
            ImportColumn::make('bola_11')->label('Bola 11')->numeric(),
            ImportColumn::make('bola_12')->label('Bola 12')->numeric(),
            ImportColumn::make('bola_13')->label('Bola 13')->numeric(),
            ImportColumn::make('bola_14')->label('Bola 14')->numeric(),
            ImportColumn::make('bola_15')->label('Bola 15')->numeric(),
            ImportColumn::make('dezenas')
                ->label('Dezenas (separadas por vírgula ou hífen)')
                ->rules(['nullable']),
        ];
    }

    public function resolveRecord(): Concurso
    {
        $numero = $this->data['concurso'] ?? $this->data['numero'] ?? null;

        $concurso = Concurso::firstOrNew([
            'concurso' => $numero,
        ]);
        
        $dezenas = [];
        
        // Verifica se vieram bolas separadas
        for ($i = 1; $i <= 15; $i++) {
            $val = $this->data['bola_' . $i] ?? $this->data['bola' . $i] ?? null;
            if (!empty($val)) {
                $dezenas[$i] = (int) $val;
            }
        }
        
        // Fallback: se não vieram 15 bolas separadas, tenta usar a coluna dezenas
        if (count($dezenas) < 15 && !empty($this->data['dezenas'])) {
            $dezStr = str_replace('-', ',', $this->data['dezenas']);
            $extracted = array_filter(array_map('intval', explode(',', $dezStr)));
            sort($extracted);
            for ($i = 1; $i <= 15 && isset($extracted[$i - 1]); $i++) {
                $dezenas[$i] = $extracted[$i - 1];
            }
        }
        
        // Atribui as 15 bolas diretamente nas colunas bola_1 ... bola_15
        for ($i = 1; $i <= 15; $i++) {
            $key = 'bola_' . $i;
            $concurso->$key = $dezenas[$i] ?? null;
        }
        
        // Format date properly if it comes as d/m/Y from Excel
        if (!empty($this->data['data_sorteio'])) {
            $date = trim((string) $this->data['data_sorteio']);
            if (str_contains($date, '/')) {
                $parts = explode('/', $date);
                if (count($parts) === 3) {
                    $concurso->data_sorteio = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
                }
            } else {
                $concurso->data_sorteio = $date;
            }
        }

        return $concurso;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'A importação de concursos foi concluída. ' . Number::format($import->successful_rows) . ' ' . str('registro')->plural($import->successful_rows) . ' importado(s).';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('registro')->plural($failedRowsCount) . ' falhou ao importar.';
        }

        return $body;
    }
}
