<?php

namespace App\Filament\App\Resources\Megasena\Schemas;

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
                Section::make('Identificação do Bolão da Mega-Sena')
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

                Section::make('Quantidade de Números Permitidos para Seleção')
                    ->description('Limites máximos de dezenas por Quadrante e por Sub-Quadrante calculados a partir da quantidade total do bolão.')
                    ->columns(4)
                    ->schema([
                        TextInput::make('max_quadrante_1')
                            ->label('Qtd Permitida em Q1 (01 a 20)')
                            ->helperText('Calculado automaticamente conforme total')
                            ->required()
                            ->numeric()
                            ->default(function () {
                                $total = (int) (\App\Models\ConfiguracaoApp::where('chave', 'megasena_total_numeros')->value('valor') ?? 10);
                                $base = (int) floor($total / 3);
                                $sobra = (int) ($total % 3);
                                return $base + ($sobra >= 1 ? 1 : 0);
                            }),
                        TextInput::make('max_quadrante_2')
                            ->label('Qtd Permitida em Q2 (21 a 40)')
                            ->helperText('Calculado automaticamente conforme total')
                            ->required()
                            ->numeric()
                            ->default(function () {
                                $total = (int) (\App\Models\ConfiguracaoApp::where('chave', 'megasena_total_numeros')->value('valor') ?? 10);
                                $base = (int) floor($total / 3);
                                $sobra = (int) ($total % 3);
                                return $base + ($sobra >= 2 ? 1 : 0);
                            }),
                        TextInput::make('max_quadrante_3')
                            ->label('Qtd Permitida em Q3 (41 a 60)')
                            ->helperText('Calculado automaticamente conforme total')
                            ->required()
                            ->numeric()
                            ->default(function () {
                                $total = (int) (\App\Models\ConfiguracaoApp::where('chave', 'megasena_total_numeros')->value('valor') ?? 10);
                                return (int) floor($total / 3);
                            }),
                        TextInput::make('max_sub_quadrante')
                            ->label('Qtd Permitida por Sub-Quadrante')
                            ->helperText('Máximo no mesmo bloco 2x2')
                            ->required()
                            ->numeric()
                            ->default(function () {
                                $total = (int) (\App\Models\ConfiguracaoApp::where('chave', 'megasena_total_numeros')->value('valor') ?? 10);
                                return max(1, (int) ceil($total / 15));
                            }),
                    ]),

                Section::make('Quantidade de Números e Participantes')
                    ->description('Defina a quantidade de dezenas a jogar. O sistema calcula automaticamente os limites por quadrante e quantas dezenas cada participante deverá selecionar.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('total_numeros')
                            ->label('Quantidade de Números a Jogar (Total do Bolão)')
                            ->helperText('Informe o total de dezenas que formarão o pool do bolão (mínimo 6, máximo 60). Ao alterar, calcula os limites por quadrante.')
                            ->required()
                            ->numeric()
                            ->minValue(6)
                            ->maxValue(60)
                            ->default(fn () => (int) (\App\Models\ConfiguracaoApp::where('chave', 'megasena_total_numeros')->value('valor') ?? 10))
                            ->live(debounce: 300)
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::calcularDezenas($get, $set);
                            }),

                        Select::make('users')
                            ->label('Participantes do Bolão')
                            ->helperText('Selecione os membros que participarão da divisão.')
                            ->multiple()
                            ->relationship('users', 'name')
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::calcularDezenas($get, $set);
                            }),

                        Placeholder::make('divisao_info')
                            ->label('Cálculo da Divisão das Dezenas')
                            ->columnSpanFull()
                            ->content(function (Get $get) {
                                $total = (int) $get('total_numeros');
                                $userIds = $get('users') ?? [];
                                $qtd = count($userIds);

                                if ($qtd === 0 || $total === 0) {
                                    return new \Illuminate\Support\HtmlString(
                                        '<div class="p-3 text-sm text-gray-400 bg-gray-900 rounded-lg border border-gray-800">
                                            ℹ️ Preencha a <strong>quantidade de números a jogar</strong> e selecione os <strong>participantes</strong> para visualizar a distribuição das dezenas.
                                        </div>'
                                    );
                                }

                                $por_pessoa = (int) floor($total / $qtd);
                                $sobra = (int) ($total % $qtd);
                                $ultimoId = end($userIds);

                                $users = \App\Models\User::whereIn('id', $userIds)->get()->keyBy('id');

                                $html = '<div class="space-y-3 p-4 bg-gray-950 rounded-xl border border-gray-800">';
                                $html .= '<div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-800 pb-3">';
                                $html .= '<div>';
                                $html .= "<span class=\"text-xs text-gray-400 uppercase tracking-wider font-semibold\">Total a Jogar:</span> ";
                                $html .= "<span class=\"text-emerald-400 font-bold text-base\">{$total} dezenas</span>";
                                $html .= " · <span class=\"text-xs text-gray-400 font-semibold\">Participantes:</span> ";
                                $html .= "<span class=\"text-white font-bold text-base\">{$qtd}</span>";
                                $html .= '</div>';

                                if ($sobra > 0) {
                                    $html .= "<div class=\"text-xs bg-amber-950 text-amber-300 border border-amber-800 px-2.5 py-1 rounded-md font-medium\">";
                                    $html .= "⚠️ Divisão não exata: {$sobra} dezena(s) adicionada(s) ao último participante";
                                    $html .= "</div>";
                                } else {
                                    $html .= "<div class=\"text-xs bg-emerald-950 text-emerald-300 border border-emerald-800 px-2.5 py-1 rounded-md font-medium\">";
                                    $html .= "✅ Divisão exata: todos com cotas iguais";
                                    $html .= "</div>";
                                }
                                $html .= '</div>';

                                $html .= '<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 pt-1">';
                                $idx = 1;
                                foreach ($userIds as $uid) {
                                    $user = $users->get($uid);
                                    $nome = $user ? $user->name : "Participante #{$uid}";
                                    $isUltimo = ($uid == $ultimoId);
                                    $minhaQtd = $isUltimo ? ($por_pessoa + $sobra) : $por_pessoa;

                                    $borderClass = $isUltimo && $sobra > 0 
                                        ? 'border-amber-600/50 bg-amber-950/20' 
                                        : 'border-gray-800 bg-gray-900/60';

                                    $badgeColor = $isUltimo && $sobra > 0 
                                        ? 'bg-amber-500/20 text-amber-300 border-amber-600' 
                                        : 'bg-emerald-500/20 text-emerald-300 border-emerald-600';

                                    $html .= "<div class=\"flex items-center justify-between p-2.5 rounded-lg border {$borderClass}\">";
                                    $html .= "<div class=\"flex items-center gap-2 truncate\">";
                                    $html .= "<span class=\"text-xs font-mono text-gray-500\">#{$idx}</span>";
                                    $html .= "<span class=\"text-sm font-medium text-gray-200 truncate\">{$nome}</span>";
                                    if ($isUltimo && $sobra > 0) {
                                        $html .= "<span class=\"text-[10px] text-amber-400 font-bold\">(Último)</span>";
                                    }
                                    $html .= "</div>";
                                    $html .= "<span class=\"text-xs font-bold px-2 py-0.5 rounded border {$badgeColor}\">{$minhaQtd} dezenas</span>";
                                    $html .= "</div>";
                                    $idx++;
                                }
                                $html .= '</div>';
                                $html .= '</div>';

                                return new \Illuminate\Support\HtmlString($html);
                            }),

                        TextInput::make('dezenas_por_participante')
                            ->hidden()
                            ->default(0),
                    ]),
            ]);
    }

    public static function calcularDezenas(Get $get, Set $set): void
    {
        $total = (int) $get('total_numeros');
        if ($total <= 0) {
            $total = (int) (\App\Models\ConfiguracaoApp::where('chave', 'megasena_total_numeros')->value('valor') ?? 10);
        }

        $users = $get('users') ?? [];
        $qtd = count($users);

        if ($qtd > 0 && $total > 0) {
            $set('dezenas_por_participante', floor($total / $qtd));
        } else {
            $set('dezenas_por_participante', 0);
        }

        // Calcula a quantidade de dezenas permitidas por quadrante
        if ($total > 0) {
            $base = (int) floor($total / 3);
            $sobra = (int) ($total % 3);

            $set('max_quadrante_1', $base + ($sobra >= 1 ? 1 : 0));
            $set('max_quadrante_2', $base + ($sobra >= 2 ? 1 : 0));
            $set('max_quadrante_3', $base);

            // Ajusta proporcionalmente o limite de sub-quadrantes (15 sub-quadrantes disponíveis)
            $maxSub = max(1, (int) ceil($total / 15));
            $set('max_sub_quadrante', $maxSub);
        }
    }
}
