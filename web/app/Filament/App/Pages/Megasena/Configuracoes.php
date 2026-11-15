<?php

namespace App\Filament\App\Pages\Megasena;

use App\Filament\Concerns\BelongsToMegasena;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\ConfiguracaoApp;

class Configuracoes extends Page implements HasForms
{
    use InteractsWithForms, BelongsToMegasena;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Configurações';
    protected ?string $heading = 'Configurações da Mega-Sena';
    protected static string|\UnitEnum|null $navigationGroup = 'Mega-Sena';
    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.app.pages.megasena.configuracoes';

    public ?array $data = [];

    public function mount(): void
    {
        $configs = ConfiguracaoApp::pluck('valor', 'chave')->toArray();
        
        $this->form->fill([
            'megasena_max_quadrante_1' => (int) ($configs['megasena_max_quadrante_1'] ?? 2),
            'megasena_max_quadrante_2' => (int) ($configs['megasena_max_quadrante_2'] ?? 2),
            'megasena_max_quadrante_3' => (int) ($configs['megasena_max_quadrante_3'] ?? 2),
            'megasena_max_sub_quadrante' => (int) ($configs['megasena_max_sub_quadrante'] ?? 1),
            'megasena_total_numeros' => (int) ($configs['megasena_total_numeros'] ?? 10),
            'megasena_janela_tendencia' => (int) ($configs['megasena_janela_tendencia'] ?? 25),
            'megasena_valor_aposta' => $configs['megasena_valor_aposta'] ?? 5.00,
            'megasena_premio_quadra' => $configs['megasena_premio_quadra'] ?? 1000.00,
            'megasena_premio_quina' => $configs['megasena_premio_quina'] ?? 50000.00,
            'megasena_premio_sena' => $configs['megasena_premio_sena'] ?? 50000000.00,
            'megasena_filtro_pares_impares' => filter_var($configs['megasena_filtro_pares_impares'] ?? '1', FILTER_VALIDATE_BOOLEAN),
            'megasena_filtro_primos' => filter_var($configs['megasena_filtro_primos'] ?? '1', FILTER_VALIDATE_BOOLEAN),
            'megasena_filtro_fibonacci' => filter_var($configs['megasena_filtro_fibonacci'] ?? '1', FILTER_VALIDATE_BOOLEAN),
            'megasena_filtro_soma' => filter_var($configs['megasena_filtro_soma'] ?? '1', FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Quantidade Padrão de Números Permitidos para Seleção')
                    ->description('Defina os limites padrão aplicados aos novos bolões para controle de seleção.')
                    ->columns(4)
                    ->schema([
                        TextInput::make('megasena_max_quadrante_1')
                            ->label('Qtd Permitida em Q1 (01 a 20)')
                            ->helperText('Padrão de dezenas selecionáveis no Q1 (ex: 2)')
                            ->numeric()
                            ->required(),
                        TextInput::make('megasena_max_quadrante_2')
                            ->label('Qtd Permitida em Q2 (21 a 40)')
                            ->helperText('Padrão de dezenas selecionáveis no Q2 (ex: 2)')
                            ->numeric()
                            ->required(),
                        TextInput::make('megasena_max_quadrante_3')
                            ->label('Qtd Permitida em Q3 (41 a 60)')
                            ->helperText('Padrão de dezenas selecionáveis no Q3 (ex: 2)')
                            ->numeric()
                            ->required(),
                        TextInput::make('megasena_max_sub_quadrante')
                            ->label('Qtd Permitida por Sub-Quadrante')
                            ->helperText('Máximo permitido dentro do mesmo bloco 2x2 (ex: 1)')
                            ->numeric()
                            ->required(),
                        TextInput::make('megasena_total_numeros')
                            ->label('Total Padrão de Números no Pool')
                            ->helperText('Quantidade padrão sugerida ao criar novos bolões')
                            ->numeric()
                            ->required()
                            ->columnSpan(2),
                        TextInput::make('megasena_janela_tendencia')
                            ->label('Janela de Tendências (Sorteios)')
                            ->helperText('Quantidade de concursos recentes para cálculo de dezenas quentes/frias')
                            ->numeric()
                            ->required()
                            ->columnSpan(2),
                    ]),

                Section::make('Filtros Inteligentes de Fechamento (Mega-Sena)')
                    ->description('Ative ou desative os filtros aplicados pelo motor combinatório na geração de cartelas.')
                    ->columns(4)
                    ->schema([
                        Toggle::make('megasena_filtro_pares_impares')
                            ->label('Pares / Ímpares')
                            ->helperText('Favorece 3P/3I, 2P/4I ou 4P/2I.'),
                        Toggle::make('megasena_filtro_primos')
                            ->label('Primos')
                            ->helperText('Exige entre 1 e 3 primos por jogo.'),
                        Toggle::make('megasena_filtro_fibonacci')
                            ->label('Fibonacci')
                            ->helperText('Exige entre 0 e 2 números de Fibonacci.'),
                        Toggle::make('megasena_filtro_soma')
                            ->label('Soma das Dezenas')
                            ->helperText('Faixa ideal entre 130 e 240.'),
                    ]),

                Section::make('Valores e Estimativas de Premiação')
                    ->columns(4)
                    ->schema([
                        TextInput::make('megasena_valor_aposta')
                            ->label('Custo da Aposta Simples (R$)')
                            ->numeric()
                            ->prefix('R$')
                            ->required(),
                        TextInput::make('megasena_premio_quadra')
                            ->label('Estimativa Quadra (R$)')
                            ->numeric()
                            ->prefix('R$')
                            ->required(),
                        TextInput::make('megasena_premio_quina')
                            ->label('Estimativa Quina (R$)')
                            ->numeric()
                            ->prefix('R$')
                            ->required(),
                        TextInput::make('megasena_premio_sena')
                            ->label('Estimativa Sena (R$)')
                            ->numeric()
                            ->prefix('R$')
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Salvar Configurações')
                ->action('save')
                ->color('success'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $chave => $valor) {
            if (is_bool($valor)) {
                $valor = $valor ? '1' : '0';
            }
            
            ConfiguracaoApp::updateOrCreate(
                ['chave' => $chave],
                ['valor' => (string) $valor]
            );
        }

        Notification::make()
            ->title('Salvo!')
            ->body('As configurações da Mega-Sena foram salvas com sucesso.')
            ->success()
            ->send();
    }
}
