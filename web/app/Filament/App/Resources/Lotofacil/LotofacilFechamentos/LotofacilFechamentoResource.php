<?php

namespace App\Filament\App\Resources\Lotofacil\LotofacilFechamentos;

use App\Filament\App\Resources\Lotofacil\LotofacilFechamentos\Pages\CreateLotofacilFechamento;
use App\Filament\App\Resources\Lotofacil\LotofacilFechamentos\Pages\EditLotofacilFechamento;
use App\Filament\App\Resources\Lotofacil\LotofacilFechamentos\Pages\ListLotofacilFechamentos;
use App\Filament\App\Resources\Lotofacil\LotofacilFechamentos\Pages\ViewLotofacilFechamento;
use App\Filament\App\Resources\Lotofacil\LotofacilFechamentos\Schemas\LotofacilFechamentoForm;
use App\Filament\App\Resources\Lotofacil\LotofacilFechamentos\Schemas\LotofacilFechamentoInfolist;
use App\Filament\App\Resources\Lotofacil\LotofacilFechamentos\Tables\LotofacilFechamentosTable;
use App\Models\Lotofacil\LotofacilFechamento;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LotofacilFechamentoResource extends Resource
{
    protected static ?string $model = LotofacilFechamento::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|\UnitEnum|null $navigationGroup = 'Lotofácil';
    protected static bool $isScopedToTenant = false;
    protected static ?string $modelLabel = 'Fechamento';
    protected static ?string $pluralModelLabel = 'Fechamentos';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return LotofacilFechamentoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LotofacilFechamentoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LotofacilFechamentosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\App\Resources\Lotofacil\LotofacilFechamentos\RelationManagers\JogosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLotofacilFechamentos::route('/'),
            'create' => CreateLotofacilFechamento::route('/create'),
            'view' => ViewLotofacilFechamento::route('/{record}'),
            'edit' => EditLotofacilFechamento::route('/{record}/edit'),
        ];
    }
}
