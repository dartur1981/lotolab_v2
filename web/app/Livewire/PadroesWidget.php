<?php

namespace App\Livewire;

use Filament\Actions\BulkActionGroup;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextColumn;
use App\Models\Lotofacil\LotofacilEstatisticaPadrao;

class PadroesWidget extends TableWidget
{
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => LotofacilEstatisticaPadrao::query())
            ->heading('Status Global (Atrasos de Padrões)')
            ->columns([
                TextColumn::make('padrao')
                    ->label('Padrão')
                    ->badge()
                    ->color('warning'),
                TextColumn::make('atraso')
                    ->label('Sorteios em Atraso')
                    ->numeric()
                    ->color(fn (string $state): string => match (true) {
                        $state == 0 => 'success',
                        $state < 3 => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('valor')
                    ->label('Frequência Histórica')
                    ->numeric(),
                TextColumn::make('percentual')
                    ->label('Frequência (%)')
                    ->suffix('%'),
            ])
            ->paginated(false);
    }
}
