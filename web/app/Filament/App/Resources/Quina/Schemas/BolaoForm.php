<?php

namespace App\Filament\App\Resources\Quina\Schemas;

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
                Section::make('Identificação do Bolão da Quina')
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
                    ->description('Limites máximos de dezenas por Quadrante calculados a partir da quantidade total do bolão.')
                    ->columns(5)
                    ->schema([
                        TextInput::make('max_quadrante_1')
                            ->label('Qtd em Q1 (01 a 20)')
                            ->helperText('Calculado conforme total')
                            ->required()
                            ->numeric()
                            ->default(function () {
                                $total = (int) (\App\Models\ConfiguracaoApp::where('chave', 'quina_total_numeros')->value('valor') ?? 10);
                                $base = (int) floor($total / 4);
                                $sobra = (int) ($total % 4);
                                return $base + ($sobra >= 1 ? 1 : 0);
                            }),
                        TextInput::make('max_quadrante_2')
                            ->label('Qtd em Q2 (21 a 40)')
                            ->helperText('Calculado conforme total')
                            ->required()
                            ->numeric()
                            ->default(function () {
                                $total = (int) (\App\Models\ConfiguracaoApp::where('chave', 'quina_total_numeros')->value('valor') ?? 10);
                                $base = (int) floor($total / 4);
                                $sobra = (int) ($total % 4);
                                return $base + ($sobra >= 2 ? 1 : 0);
                            }),
                        TextInput::make('max_quadrante_3')
                            ->label('Qtd em Q3 (41 a 60)')
                            ->helperText('Calculado conforme total')
                            ->required()
                            ->numeric()
                            ->default(function () {
                                $total = (int) (\App\Models\ConfiguracaoApp::where('chave', 'quina_total_numeros')->value('valor') ?? 10);
                                $base = (int) floor($total / 4);
                                $sobra = (int) ($total % 4);
                                return $base + ($sobra >= 3 ? 1 : 0);
                            }),
                        TextInput::make('max_quadrante_4')
                            ->label('Qtd em Q4 (61 a 80)')
                            ->helperText('Calculado conforme total')
                            ->required()
                            ->numeric()
                            ->default(function () {
                                $total = (int) (\App\Models\ConfiguracaoApp::where('chave', 'quina_total_numeros')->value('valor') ?? 10);
                                return (int) floor($total / 4);
                            }),
                        TextInput::make('max_sub_quadrante')
                            ->label('Qtd por Sub-Quadrante (2x2)')
                            ->helperText('Máximo por bloco 2x2')
                            ->required()
                            ->numeric()
                            ->default(1),
                    ]),

                Section::make('Quantidade de Números e Participantes')
                    ->description('Defina a quantidade de dezenas a jogar. O sistema calcula automaticamente os limites por quadrante e quantas dezenas cada participante deverá selecionar.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('total_numeros')
                            ->label('Quantidade de Números a Jogar (Total do Bolão)')
                            ->helperText('Informe o total de dezenas que formarão o pool do bolão (mínimo 5, máximo 80). Ao alterar, calcula os limites por quadrante.')
                            ->required()
                            ->numeric()
                            ->minValue(5)
                            ->maxValue(80)
                            ->default(fn () => (int) (\App\Models\ConfiguracaoApp::where('chave', 'quina_total_numeros')->value('valor') ?? 10))
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

                                $porPessoa = (int) floor($total / $qtd);
                                $sobra = (int) ($total % $qtd);

                                $users = \App\Models\User::whereIn('id', $userIds)->get()->keyBy('id');
                                $linhas = '';
                                $totalConfirmado = 0;

                                foreach ($userIds as $index => $id) {
                                    $nome = $users->get($id)?->name ?? "Usuário #{$id}";
                                    $ehUltimo = ($index === $qtd - 1);
                                    $qtdFinal = $ehUltimo ? ($porPessoa + $sobra) : $porPessoa;
                                    $totalConfirmado += $qtdFinal;
                                    $destaque = $ehUltimo && $sobra > 0
                                        ? '<span class="text-xs text-amber-400 font-normal ml-1">(+ ' . $sobra . ' da sobra)</span>'
                                        : '';

                                    $linhas .= '<tr class="border-b border-gray-800">
                                        <td class="py-1 px-3 text-gray-300">' . htmlspecialchars($nome) . '</td>
                                        <td class="py-1 px-3 text-right font-bold text-emerald-400">' . $qtdFinal . ' dezenas' . $destaque . '</td>
                                    </tr>';
                                }

                                return new \Illuminate\Support\HtmlString(
                                    '<div class="rounded-lg border border-gray-800 bg-gray-900 overflow-hidden text-sm">
                                        <div class="p-3 bg-gray-800/60 font-semibold text-gray-200 flex justify-between items-center">
                                            <span>📋 Distribuição Automática: ' . $qtd . ' participante(s) para ' . $total . ' dezenas</span>
                                            <span class="text-xs font-normal text-gray-400">Total: <strong class="text-white">' . $totalConfirmado . '</strong> dezenas</span>
                                        </div>
                                        <table class="w-full text-left">
                                            <thead>
                                                <tr class="text-xs text-gray-400 border-b border-gray-800 bg-gray-900/50">
                                                    <th class="py-2 px-3">Participante</th>
                                                    <th class="py-2 px-3 text-right">Cota de Dezenas</th>
                                                </tr>
                                            </thead>
                                            <tbody>' . $linhas . '</tbody>
                                        </table>
                                    </div>'
                                );
                            }),
                    ]),
            ]);
    }

    public static function calcularDezenas(Get $get, Set $set): void
    {
        $total = (int) $get('total_numeros');
        if ($total <= 0) return;

        // Quina divide entre 4 quadrantes
        $base = (int) floor($total / 4);
        $sobra = (int) ($total % 4);

        $set('max_quadrante_1', $base + ($sobra >= 1 ? 1 : 0));
        $set('max_quadrante_2', $base + ($sobra >= 2 ? 1 : 0));
        $set('max_quadrante_3', $base + ($sobra >= 3 ? 1 : 0));
        $set('max_quadrante_4', $base);

        $userIds = $get('users') ?? [];
        $qtd = count($userIds);
        if ($qtd > 0) {
            $set('dezenas_por_participante', (int) floor($total / $qtd));
        }
    }
}
