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
            ImportColumn::make('numero')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('data_sorteio')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('bola1')->numeric(),
            ImportColumn::make('bola2')->numeric(),
            ImportColumn::make('bola3')->numeric(),
            ImportColumn::make('bola4')->numeric(),
            ImportColumn::make('bola5')->numeric(),
            ImportColumn::make('bola6')->numeric(),
            ImportColumn::make('bola7')->numeric(),
            ImportColumn::make('bola8')->numeric(),
            ImportColumn::make('bola9')->numeric(),
            ImportColumn::make('bola10')->numeric(),
            ImportColumn::make('bola11')->numeric(),
            ImportColumn::make('bola12')->numeric(),
            ImportColumn::make('bola13')->numeric(),
            ImportColumn::make('bola14')->numeric(),
            ImportColumn::make('bola15')->numeric(),
            ImportColumn::make('dezenas') // fallback caso venha tudo junto
                ->rules(['nullable']),
        ];
    }

    public function resolveRecord(): Concurso
    {
        $concurso = Concurso::firstOrNew([
            'numero' => $this->data['numero'],
        ]);
        
        $dezenas = [];
        
        // Verifica se vieram bolas separadas
        for ($i = 1; $i <= 15; $i++) {
            $key = 'bola' . $i;
            if (!empty($this->data[$key])) {
                $dezenas[] = (int) $this->data[$key];
            }
        }
        
        // Fallback: se não vieram bolas, tenta usar a coluna dezenas
        if (empty($dezenas) && !empty($this->data['dezenas'])) {
            $dezStr = str_replace('-', ',', $this->data['dezenas']);
            $dezenas = array_map('intval', explode(',', $dezStr));
        }
        
        sort($dezenas);
        $concurso->dezenas = $dezenas;
        
        // Format date properly if it comes as d/m/Y from Excel
        if (!empty($this->data['data_sorteio'])) {
            $date = $this->data['data_sorteio'];
            if (str_contains($date, '/')) {
                $parts = explode('/', $date);
                if (count($parts) == 3) {
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
        $body = 'Your concurso import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
