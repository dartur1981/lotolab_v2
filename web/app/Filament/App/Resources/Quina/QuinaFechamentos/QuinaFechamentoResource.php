<?php

namespace App\Filament\App\Resources\Quina\QuinaFechamentos;

use App\Filament\App\Resources\Quina\QuinaFechamentos\Pages\CreateQuinaFechamento;
use App\Filament\App\Resources\Quina\QuinaFechamentos\Pages\EditQuinaFechamento;
use App\Filament\App\Resources\Quina\QuinaFechamentos\Pages\ListQuinaFechamentos;
use App\Filament\App\Resources\Quina\QuinaFechamentos\Pages\ViewQuinaFechamento;
use App\Filament\App\Resources\Quina\QuinaFechamentos\RelationManagers\JogosRelationManager;
use App\Filament\App\Resources\Quina\QuinaFechamentos\Schemas\QuinaFechamentoForm;
use App\Filament\App\Resources\Quina\QuinaFechamentos\Schemas\QuinaFechamentoInfolist;
use App\Filament\App\Resources\Quina\QuinaFechamentos\Tables\QuinaFechamentosTable;
use App\Filament\Concerns\BelongsToQuina;
use App\Models\Quina\QuinaFechamento;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class QuinaFechamentoResource extends Resource
{
    use BelongsToQuina;

    protected static ?string $model = QuinaFechamento::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|\UnitEnum|null $navigationGroup = 'Quina';
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
        return QuinaFechamentoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return QuinaFechamentoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuinaFechamentosTable::configure($table);
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
            'index' => ListQuinaFechamentos::route('/'),
            'create' => CreateQuinaFechamento::route('/create'),
            'view' => ViewQuinaFechamento::route('/{record}'),
            'edit' => EditQuinaFechamento::route('/{record}/edit'),
        ];
    }
}
