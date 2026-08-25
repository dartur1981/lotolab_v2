<?php

namespace App\Livewire;

use Filament\Actions\BulkActionGroup;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextColumn;
use App\Models\Lotofacil\LotofacilEstatisticaTrinca;

class TrincasWidget extends TableWidget
{
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => LotofacilEstatisticaTrinca::query()->orderByDesc('frequencia')->limit(20))
            ->heading('Top 20 Trincas Mais Sorteadas')
            ->columns([
                TextColumn::make('dezenas')
                    ->label('Trinca (Dezenas)')
                    ->badge()
                    ->color('success')
                    ->searchable(),
                TextColumn::make('frequencia')
                    ->label('Vezes Sorteadas')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('percentual')
                    ->label('Frequência (%)')
                    ->suffix('%')
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
