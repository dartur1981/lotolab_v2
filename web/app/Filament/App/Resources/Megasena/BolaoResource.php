<?php

namespace App\Filament\App\Resources\Megasena;

use App\Filament\App\Resources\Megasena\Pages\CreateBolao;
use App\Filament\App\Resources\Megasena\Pages\EditBolao;
use App\Filament\App\Resources\Megasena\Pages\ListBolaos;
use App\Filament\App\Resources\Megasena\Pages\SelecoesBolao;
use App\Filament\App\Resources\Megasena\Schemas\BolaoForm;
use App\Filament\App\Resources\Megasena\Tables\BolaosTable;
use App\Filament\Concerns\BelongsToMegasena;
use App\Models\Megasena\Bolao;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BolaoResource extends Resource
{
    use BelongsToMegasena;

    protected static ?string $model = Bolao::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;
    protected static string|\UnitEnum|null $navigationGroup = 'Mega-Sena';
    protected static ?string $navigationLabel = 'Cadastro de Bolão';
    protected static ?string $modelLabel = 'Cadastro de Bolão';
    protected static ?string $pluralModelLabel = 'Cadastro de Bolões';

    protected static bool $isScopedToTenant = false;

    protected static ?string $recordTitleAttribute = 'nome';

    public static function form(Schema $schema): Schema
    {
        return BolaoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BolaosTable::configure($table);
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
            'index' => ListBolaos::route('/'),
            'create' => CreateBolao::route('/create'),
            'edit' => EditBolao::route('/{record}/edit'),
            'selecoes' => SelecoesBolao::route('/{record}/selecoes'),
        ];
    }
}
