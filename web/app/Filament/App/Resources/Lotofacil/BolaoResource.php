<?php

namespace App\Filament\App\Resources\Lotofacil;


use App\Filament\App\Resources\Lotofacil\Pages\CreateBolao;
use App\Filament\App\Resources\Lotofacil\Pages\EditBolao;
use App\Filament\App\Resources\Lotofacil\Pages\ListBolaos;
use App\Filament\App\Resources\Lotofacil\Schemas\BolaoForm;
use App\Filament\App\Resources\Lotofacil\Tables\BolaosTable;
use App\Models\Lotofacil\Bolao;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BolaoResource extends Resource
{
    protected static ?string $model = Bolao::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;
    protected static string|\UnitEnum|null $navigationGroup = 'Lotofácil';
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

    public static function getWidgets(): array
    {
        return [
            \App\Filament\App\Resources\Lotofacil\Widgets\BolaoStatsWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBolaos::route('/'),
            'create' => CreateBolao::route('/create'),
            'edit' => EditBolao::route('/{record}/edit'),
            'selecoes' => \App\Filament\App\Resources\Lotofacil\Pages\SelecoesBolao::route('/{record}/selecoes'),
        ];
    }
}
