<?php

namespace App\Filament\Resources\Loterias;

use App\Filament\Resources\Loterias\Pages\CreateLoteria;
use App\Filament\Resources\Loterias\Pages\EditLoteria;
use App\Filament\Resources\Loterias\Pages\ListLoterias;
use App\Filament\Resources\Loterias\Schemas\LoteriaForm;
use App\Filament\Resources\Loterias\Tables\LoteriasTable;
use App\Models\Loteria;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LoteriaResource extends Resource
{
    protected static ?string $model = Loteria::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'nome';

    public static function form(Schema $schema): Schema
    {
        return LoteriaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LoteriasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLoterias::route('/'),
            'create' => CreateLoteria::route('/create'),
            'edit' => EditLoteria::route('/{record}/edit'),
        ];
    }
}
