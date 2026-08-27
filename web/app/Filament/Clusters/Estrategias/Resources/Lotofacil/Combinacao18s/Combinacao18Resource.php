<?php

namespace App\Filament\Clusters\Estrategias\Resources\Lotofacil\Combinacao18s;

use App\Filament\Clusters\Estrategias\EstrategiasCluster;
use App\Filament\Clusters\Estrategias\Resources\Lotofacil\Combinacao18s\Pages\ManageCombinacao18s;
use App\Models\Lotofacil\Combinacao18;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class Combinacao18Resource extends Resource
{
    protected static ?string $model = Combinacao18::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-group';

    protected static bool $isScopedToTenant = false;

    protected static ?string $cluster = EstrategiasCluster::class;

    protected static ?string $modelLabel = 'Combinação de 18 Dezenas';
    protected static ?string $pluralModelLabel = 'Combinações de 18 Dezenas';
    protected static ?string $navigationLabel = 'Combinações de 18';

    protected static ?string $recordTitleAttribute = 'dezenas';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('dezenas'),
                TextInput::make('pares')->numeric(),
                TextInput::make('impares')->numeric(),
                TextInput::make('primos')->numeric(),
                TextInput::make('fibonacci')->numeric(),
                TextInput::make('moldura')->numeric(),
                TextInput::make('miolo')->numeric(),
                TextInput::make('soma')->numeric(),
                TextInput::make('acertos_15')->numeric(),
                TextInput::make('acertos_14')->numeric(),
                TextInput::make('acertos_13')->numeric(),
                TextInput::make('acertos_12')->numeric(),
                TextInput::make('acertos_11')->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('dezenas')
            ->columns([
                TextColumn::make('dezenas')
                    ->label('Dezenas (18)')
                    ->searchable()
                    ->badge()
                    ->separator(',')
                    ->color('info'),
                TextColumn::make('pares')
                    ->label('Pares')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('impares')
                    ->label('Ímpares')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('primos')
                    ->label('Primos')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('fibonacci')
                    ->label('Fibo')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('moldura')
                    ->label('Moldura')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('miolo')
                    ->label('Miolo')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('soma')
                    ->label('Soma')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('acertos_15')
                    ->label('15 Pts')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('acertos_14')
                    ->label('14 Pts')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('acertos_13')
                    ->label('13 Pts')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('acertos_12')
                    ->label('12 Pts')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('acertos_11')
                    ->label('11 Pts')
                    ->numeric()
                    ->sortable(),
            ])
            ->defaultSort('acertos_15', 'desc')
            ->filters([
                //
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCombinacao18s::route('/'),
        ];
    }
}
