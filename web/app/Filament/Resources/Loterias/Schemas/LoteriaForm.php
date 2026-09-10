<?php

namespace App\Filament\Resources\Loterias\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ColorPicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class LoteriaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Dados da Loteria')
                    ->columns(2)
                    ->schema([
                        TextInput::make('nome')
                            ->label('Nome da Loteria')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, $state, callable $set) => 
                                $operation === 'create' ? $set('slug', Str::slug($state)) : null
                            ),

                        TextInput::make('slug')
                            ->label('Identificador (Slug)')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        TextInput::make('total_de_numeros')
                            ->label('Total de Números do Volante')
                            ->helperText('Ex: 25 para Lotofácil, 60 para Mega-Sena')
                            ->required()
                            ->numeric()
                            ->default(25),

                        TextInput::make('numeros_por_jogo')
                            ->label('Números por Aposta')
                            ->helperText('Ex: 15 para Lotofácil, 6 para Mega-Sena')
                            ->required()
                            ->numeric()
                            ->default(15),

                        ColorPicker::make('cor_padrao')
                            ->label('Cor Padrão')
                            ->default('#9333ea'),

                        Toggle::make('ativo')
                            ->label('Loteria Ativa no Sistema')
                            ->default(true)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
