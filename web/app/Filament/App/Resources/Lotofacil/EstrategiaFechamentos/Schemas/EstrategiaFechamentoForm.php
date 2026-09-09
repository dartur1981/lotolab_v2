<?php

namespace App\Filament\App\Resources\Lotofacil\EstrategiaFechamentos\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EstrategiaFechamentoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('quantidade_jogos')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('dezenas'),
                TextInput::make('grupos'),
            ]);
    }
}
