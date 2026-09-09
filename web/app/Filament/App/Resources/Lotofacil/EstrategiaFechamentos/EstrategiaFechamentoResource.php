<?php

namespace App\Filament\App\Resources\Lotofacil\EstrategiaFechamentos;

use App\Filament\App\Resources\Lotofacil\EstrategiaFechamentos\Pages\CreateEstrategiaFechamento;
use App\Filament\App\Resources\Lotofacil\EstrategiaFechamentos\Pages\EditEstrategiaFechamento;
use App\Filament\App\Resources\Lotofacil\EstrategiaFechamentos\Pages\ListEstrategiaFechamentos;
use App\Filament\App\Resources\Lotofacil\EstrategiaFechamentos\Pages\ViewEstrategiaFechamento;
use App\Filament\App\Resources\Lotofacil\EstrategiaFechamentos\Schemas\EstrategiaFechamentoForm;
use App\Filament\App\Resources\Lotofacil\EstrategiaFechamentos\Schemas\EstrategiaFechamentoInfolist;
use App\Filament\App\Resources\Lotofacil\EstrategiaFechamentos\Tables\EstrategiaFechamentosTable;
use App\Models\Lotofacil\EstrategiaFechamento;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EstrategiaFechamentoResource extends Resource
{
    protected static ?string $model = EstrategiaFechamento::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';
    
    protected static ?string $cluster = \App\Filament\Clusters\Estrategias\EstrategiasCluster::class;
    
    protected static bool $isScopedToTenant = false;
    protected static ?int $navigationSort = 2;
    
    protected static ?string $modelLabel = 'Fechamento Salvo';
    protected static ?string $pluralModelLabel = 'Fechamentos Salvos';
    protected static ?string $navigationLabel = 'Fechamentos Salvos';
    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return EstrategiaFechamentoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EstrategiaFechamentoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EstrategiaFechamentosTable::configure($table);
    }



    public static function getRelations(): array
    {
        return [
            RelationManagers\EstrategiaJogosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEstrategiaFechamentos::route('/'),
            'create' => CreateEstrategiaFechamento::route('/create'),
            'view' => ViewEstrategiaFechamento::route('/{record}'),
            'edit' => EditEstrategiaFechamento::route('/{record}/edit'),
        ];
    }
}
