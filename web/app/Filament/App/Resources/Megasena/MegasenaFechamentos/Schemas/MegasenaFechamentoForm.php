<?php

namespace App\Filament\App\Resources\Megasena\MegasenaFechamentos\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MegasenaFechamentoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Identificação do Fechamento')
                    ->schema([
                        TextInput::make('bolao_nome')
                            ->label('Bolão Vinculado')
                            ->formatStateUsing(fn ($record) => $record && $record->bolao ? 'Fechamento Mega-Sena – ' . $record->bolao->nome : '')
                            ->disabled()
                            ->columnSpanFull(),

                        TextInput::make('quantidade_jogos')
                            ->label('Quantidade de Jogos (Cartelas)')
                            ->numeric()
                            ->default(10)
                            ->disabled(fn ($record) => $record !== null),
                    ]),

                Section::make('Resultado Oficial da Mega-Sena (opcional)')
                    ->description('Informe as 6 dezenas sorteadas da Mega-Sena para calcular a conferência de acertos.')
                    ->schema([
                        Placeholder::make('tags_style')
                            ->hiddenLabel()
                            ->content(new \Illuminate\Support\HtmlString('
                                <style>
                                    .resultado-tags .fi-badge {
                                        background-color: rgba(16, 185, 129, 0.2) !important;
                                        color: rgb(5, 150, 105) !important;
                                        border: 1px solid rgba(16, 185, 129, 0.3);
                                    }
                                </style>
                            ')),
                        TagsInput::make('resultado')
                            ->label('')
                            ->extraAttributes(['class' => 'resultado-tags'])
                            ->placeholder('Digite uma dezena (01 a 60) e pressione Enter')
                            ->formatStateUsing(function ($state) {
                                if (empty($state)) return [];
                                if (is_string($state)) {
                                    $decoded = json_decode($state, true);
                                    if (is_array($decoded)) return $decoded;
                                    return explode(',', $state);
                                }
                                return is_array($state) ? $state : [];
                            })
                            ->live()
                            ->afterStateUpdated(function (?array $state, $set) {
                                $state = $state ?? [];
                                $cleaned = [];
                                foreach ($state as $val) {
                                    $num = intval(trim($val));
                                    if ($num >= 1 && $num <= 60) {
                                        $cleaned[] = (string) $num;
                                    }
                                }
                                $uniqueState = array_values(array_unique($cleaned));
                                if ($uniqueState !== $state) {
                                    $set('resultado', $uniqueState);
                                    if (count($uniqueState) < count($state)) {
                                        \Filament\Notifications\Notification::make()
                                            ->title('Dezena repetida ou inválida removida!')
                                            ->warning()
                                            ->send();
                                    }
                                }
                            })
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                ViewField::make('resultado_estatisticas')
                    ->view('filament.app.infolists.components.fechamento-stats')
                    ->columnSpanFull()
                    ->hiddenLabel(),

                ViewField::make('bot_log')
                    ->view('filament.app.components.bot-log-embed')
                    ->columnSpanFull()
                    ->hiddenLabel(),
            ]);
    }
}
