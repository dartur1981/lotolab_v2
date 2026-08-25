<?php

namespace App\Filament\App\Resources\Lotofacil\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class BolaoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Identificação do Bolão')
                    ->columns(3)
                    ->schema([
                        TextInput::make('nome')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                0 => 'Aguardando',
                                1 => 'Ativo',
                                2 => 'Encerrado',
                                3 => 'Apostado',
                                4 => 'Apurado',
                            ])
                            ->required()
                            ->default(0),
                        TextInput::make('concurso_alvo')
                            ->label('Concurso Alvo (Opcional)')
                            ->numeric(),
                        TextInput::make('valor_cota')
                            ->label('Valor da Cota (R$)')
                            ->required()
                            ->numeric()
                            ->prefix('R$')
                            ->default(0.0),
                        TextInput::make('valor_total')
                            ->label('Valor Total (R$)')
                            ->required()
                            ->numeric()
                            ->prefix('R$')
                            ->default(0.0),
                    ]),

                Section::make('Parâmetros Matemáticos')
                    ->description('Limites do filtro (Valores estatísticos comprovados: Moldura=11, Miolo=6, Linha=4, Coluna=4)')
                    ->columns(4)
                    ->schema([
                        TextInput::make('max_moldura')
                            ->required()
                            ->numeric()
                            ->default(12),
                        TextInput::make('max_miolo')
                            ->required()
                            ->numeric()
                            ->default(6),
                        TextInput::make('max_linha')
                            ->required()
                            ->numeric()
                            ->default(4),
                        TextInput::make('max_coluna')
                            ->required()
                            ->numeric()
                            ->default(4),
                    ]),

                Section::make('Participantes e Divisão de Dezenas')
                    ->description('Selecione os participantes do bolão. O sistema calculará a divisão automaticamente com base no Total de Números.')
                    ->schema([
                        Select::make('users')
                            ->label('Participantes')
                            ->multiple()
                            ->relationship('users', 'name')
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::calcularDezenas($get, $set);
                            }),

                        TextInput::make('total_numeros')
                            ->label('Total de Números do Jogo')
                            ->required()
                            ->numeric()
                            ->default(18)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::calcularDezenas($get, $set);
                            }),

                        Placeholder::make('divisao_info')
                            ->label('Resumo da Divisão')
                            ->content(function (Get $get) {
                                $total = (int) $get('total_numeros');
                                $users = $get('users') ?? [];
                                $qtd = count($users);

                                if ($qtd === 0 || $total === 0) {
                                    return 'Selecione os participantes para calcular a divisão.';
                                }

                                $por_pessoa = floor($total / $qtd);
                                $sobra = $total % $qtd;

                                $ultimo = $por_pessoa + $sobra;

                                return "Com {$qtd} participante(s), cada um escolherá {$por_pessoa} dezena(s). O último participante selecionado escolherá {$ultimo} dezena(s).";
                            }),

                        TextInput::make('dezenas_por_participante')
                            ->hidden() // Será salvo silenciosamente
                            ->default(0),
                    ]),
            ]);
    }

    public static function calcularDezenas(Get $get, Set $set): void
    {
        $total = (int) $get('total_numeros');
        $users = $get('users') ?? [];
        $qtd = count($users);

        if ($qtd > 0 && $total > 0) {
            $set('dezenas_por_participante', floor($total / $qtd));
        } else {
            $set('dezenas_por_participante', 0);
        }
    }
}
