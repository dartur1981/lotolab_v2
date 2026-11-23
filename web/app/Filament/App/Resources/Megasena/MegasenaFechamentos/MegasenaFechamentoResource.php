<?php

namespace App\Filament\App\Resources\Megasena\MegasenaFechamentos;

use App\Filament\App\Resources\Megasena\MegasenaFechamentos\Pages\CreateMegasenaFechamento;
use App\Filament\App\Resources\Megasena\MegasenaFechamentos\Pages\EditMegasenaFechamento;
use App\Filament\App\Resources\Megasena\MegasenaFechamentos\Pages\ListMegasenaFechamentos;
use App\Filament\App\Resources\Megasena\MegasenaFechamentos\Pages\ViewMegasenaFechamento;
use App\Filament\App\Resources\Megasena\MegasenaFechamentos\RelationManagers\JogosRelationManager;
use App\Filament\App\Resources\Megasena\MegasenaFechamentos\Schemas\MegasenaFechamentoForm;
use App\Filament\App\Resources\Megasena\MegasenaFechamentos\Schemas\MegasenaFechamentoInfolist;
use App\Filament\App\Resources\Megasena\MegasenaFechamentos\Tables\MegasenaFechamentosTable;
use App\Filament\Concerns\BelongsToMegasena;
use App\Models\Megasena\MegasenaFechamento;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MegasenaFechamentoResource extends Resource
{
    use BelongsToMegasena;

    protected static ?string $model = MegasenaFechamento::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|\UnitEnum|null $navigationGroup = 'Mega-Sena';
    protected static bool $isScopedToTenant = false;
    protected static ?string $modelLabel = 'Fechamento';
    protected static ?string $pluralModelLabel = 'Fechamentos';

    protected static ?string $recordTitleAttribute = 'id';

    public static function canCreate(): bool
    {
        return true;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return true;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return true;
    }

    public static function canView(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return true;
    }

    public static function form(Schema $schema): Schema
    {
        return MegasenaFechamentoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MegasenaFechamentoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MegasenaFechamentosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            JogosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMegasenaFechamentos::route('/'),
            'create' => CreateMegasenaFechamento::route('/create'),
            'view' => ViewMegasenaFechamento::route('/{record}'),
            'edit' => EditMegasenaFechamento::route('/{record}/edit'),
        ];
    }
}
