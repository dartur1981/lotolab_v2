<?php

namespace App\Filament\App\Resources\Lotofacil\LotofacilFechamentos\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LotofacilFechamentoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                \Filament\Schemas\Components\Section::make('Identificação')
                    ->schema([
                        TextInput::make('bolao_nome')
                            ->label('Nome do fechamento')
                            ->formatStateUsing(fn ($record) => $record ? 'Fechamento - ' . $record->bolao->nome : '')
                            ->disabled()
                            ->columnSpanFull(),
                    ]),

                \Filament\Schemas\Components\Section::make('Matriz 1')
                    ->description('Grupo A com 6 dezenas para cruzamento.')
                    ->schema([
                        \Filament\Forms\Components\TagsInput::make('grupos.grupos.A')
                            ->label('')
                            ->live()
                            ->afterStateUpdated(function (?array $state, $set) {
                                $cleaned = [];
                                foreach ($state ?? [] as $val) {
                                    $num = intval(trim($val));
                                    if ($num >= 1 && $num <= 25) $cleaned[] = (string) $num;
                                }
                                $unique = array_values(array_unique($cleaned));
                                if ($unique !== $state) {
                                    $set('grupos.grupos.A', $unique);
                                    if (count($unique) < count($state)) {
                                        \Filament\Notifications\Notification::make()
                                            ->title('Dezena repetida ou inválida removida!')
                                            ->warning()
                                            ->send();
                                    }
                                }
                            })
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),

                \Filament\Schemas\Components\Section::make('Matriz 2')
                    ->description('Grupo B com 6 dezenas para cruzamento.')
                    ->schema([
                        \Filament\Forms\Components\TagsInput::make('grupos.grupos.B')
                            ->label('')
                            ->live()
                            ->afterStateUpdated(function (?array $state, $set) {
                                $cleaned = [];
                                foreach ($state ?? [] as $val) {
                                    $num = intval(trim($val));
                                    if ($num >= 1 && $num <= 25) $cleaned[] = (string) $num;
                                }
                                $unique = array_values(array_unique($cleaned));
                                if ($unique !== $state) {
                                    $set('grupos.grupos.B', $unique);
                                    if (count($unique) < count($state)) {
                                        \Filament\Notifications\Notification::make()
                                            ->title('Dezena repetida ou inválida removida!')
                                            ->warning()
                                            ->send();
                                    }
                                }
                            })
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),

                \Filament\Schemas\Components\Section::make('Matriz 3')
                    ->description('Grupo C com 6 dezenas para cruzamento.')
                    ->schema([
                        \Filament\Forms\Components\TagsInput::make('grupos.grupos.C')
                            ->label('')
                            ->live()
                            ->afterStateUpdated(function (?array $state, $set) {
                                $cleaned = [];
                                foreach ($state ?? [] as $val) {
                                    $num = intval(trim($val));
                                    if ($num >= 1 && $num <= 25) $cleaned[] = (string) $num;
                                }
                                $unique = array_values(array_unique($cleaned));
                                if ($unique !== $state) {
                                    $set('grupos.grupos.C', $unique);
                                    if (count($unique) < count($state)) {
                                        \Filament\Notifications\Notification::make()
                                            ->title('Dezena repetida ou inválida removida!')
                                            ->warning()
                                            ->send();
                                    }
                                }
                            })
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),

                \Filament\Schemas\Components\Section::make('Resultado (opcional)')
                    ->description('Informe as 15 dezenas sorteadas para calcular acertos.')
                    ->schema([
                        \Filament\Forms\Components\Placeholder::make('tags_style')
                            ->hiddenLabel()
                            ->content(new \Illuminate\Support\HtmlString('
                                <style>
                                    .resultado-tags .fi-badge {
                                        background-color: rgba(34, 197, 94, 0.2) !important;
                                        color: rgb(21, 128, 61) !important;
                                        border: 1px solid rgba(34, 197, 94, 0.3);
                                    }
                                    .resultado-tags .fi-badge:nth-child(n+17) {
                                        background-color: rgba(239, 68, 68, 0.2) !important;
                                        color: rgb(185, 28, 28) !important;
                                        border: 1px solid rgba(239, 68, 68, 0.3);
                                    }
                                </style>
                            ')),
                        \Filament\Forms\Components\TagsInput::make('resultado')
                            ->label('')
                            ->extraAttributes(['class' => 'resultado-tags'])
                            ->placeholder('Adicione as dezenas e pressione Enter')
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
                                    if ($num >= 1 && $num <= 25) {
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
                                    $state = $uniqueState;
                                }

                                if (count($state) === 16) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('15 dezenas ultrapassadas!')
                                        ->body('Você está inserindo dezenas extras para simulação.')
                                        ->warning()
                                        ->send();
                                }
                            })
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
                    
                \Filament\Forms\Components\ViewField::make('bot_log')
                    ->view('filament.app.components.bot-log-embed')
                    ->columnSpanFull()
                    ->hiddenLabel(),
            ]);
    }
}
