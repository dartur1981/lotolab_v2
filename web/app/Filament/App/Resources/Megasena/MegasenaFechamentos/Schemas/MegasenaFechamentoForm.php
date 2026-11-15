<?php

namespace App\Filament\App\Resources\Megasena\MegasenaFechamentos\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MegasenaFechamentoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('bolao_id')
                    ->label('Bolão da Mega-Sena')
                    ->relationship('bolao', 'nome')
                    ->required(),
                TextInput::make('quantidade_jogos')
                    ->label('Quantidade de Jogos')
                    ->numeric()
                    ->default(10)
                    ->required(),
            ]);
    }
}
