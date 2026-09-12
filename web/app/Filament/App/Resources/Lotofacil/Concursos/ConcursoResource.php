<?php

namespace App\Filament\App\Resources\Lotofacil\Concursos;

use App\Filament\App\Resources\Lotofacil\Concursos\Pages\ManageConcursos;
use App\Models\Lotofacil\Concurso;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ConcursoResource extends Resource
{
    protected static ?string $model = Concurso::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|\UnitEnum|null $navigationGroup = 'Lotofácil';
    protected static bool $isScopedToTenant = false;
    protected static ?string $modelLabel = 'Concurso';
    protected static ?string $pluralModelLabel = 'Concursos';
    protected static ?string $navigationLabel = 'Concursos';

    protected static ?string $recordTitleAttribute = 'concurso';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('concurso')
                    ->label('Número do Concurso')
                    ->required()
                    ->numeric(),
                DatePicker::make('data_sorteio')
                    ->label('Data do Sorteio')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('concurso')
            ->columns([
                TextColumn::make('concurso')
                    ->label('Concurso')
                    ->numeric()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('data_sorteio')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('dezenas')
                    ->label('Dezenas Sorteadas')
                    ->badge()
                    ->color('primary'),
            ])
            ->defaultSort('concurso', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageConcursos::route('/'),
        ];
    }
}
