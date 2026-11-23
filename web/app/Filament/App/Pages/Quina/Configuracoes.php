<?php

namespace App\Filament\App\Pages\Quina;

use App\Filament\Concerns\BelongsToQuina;
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
    use InteractsWithForms, BelongsToQuina;

    protected static ?string $slug = 'quina/configuracoes';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Configurações';
    protected ?string $heading = 'Configurações da Quina';
    protected static string|\UnitEnum|null $navigationGroup = 'Quina';
    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.app.pages.quina.configuracoes';

    public ?array $data = [];

    public function mount(): void
    {
        $configs = ConfiguracaoApp::pluck('valor', 'chave')->toArray();
        
        $this->form->fill([
            'quina_max_quadrante_1' => (int) ($configs['quina_max_quadrante_1'] ?? 3),
            'quina_max_quadrante_2' => (int) ($configs['quina_max_quadrante_2'] ?? 3),
            'quina_max_quadrante_3' => (int) ($configs['quina_max_quadrante_3'] ?? 3),
            'quina_max_quadrante_4' => (int) ($configs['quina_max_quadrante_4'] ?? 3),
            'quina_max_sub_quadrante' => (int) ($configs['quina_max_sub_quadrante'] ?? 1),
            'quina_total_numeros' => (int) ($configs['quina_total_numeros'] ?? 15),
            'quina_valor_aposta' => $configs['quina_valor_aposta'] ?? 2.50,
            'quina_premio_duque' => $configs['quina_premio_duque'] ?? 4.50,
            'quina_premio_terno' => $configs['quina_premio_terno'] ?? 150.00,
            'quina_premio_quadra' => $configs['quina_premio_quadra'] ?? 8000.00,
            'quina_premio_quina' => $configs['quina_premio_quina'] ?? 10000000.00,
            'quina_filtro_pares_impares' => filter_var($configs['quina_filtro_pares_impares'] ?? '1', FILTER_VALIDATE_BOOLEAN),
            'quina_filtro_primos' => filter_var($configs['quina_filtro_primos'] ?? '1', FILTER_VALIDATE_BOOLEAN),
            'quina_filtro_fibonacci' => filter_var($configs['quina_filtro_fibonacci'] ?? '1', FILTER_VALIDATE_BOOLEAN),
            'quina_filtro_soma' => filter_var($configs['quina_filtro_soma'] ?? '1', FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Quantidade Padrão de Números Permitidos por Quadrante')
                    ->description('Defina os limites padrão aplicados aos 4 quadrantes da Quina nos novos bolões.')
                    ->columns(4)
                    ->schema([
                        TextInput::make('quina_max_quadrante_1')
                            ->label('Qtd em Q1 (01 a 20)')
                            ->helperText('Máximo no Quadrante 1 (ex: 3)')
                            ->numeric()
                            ->required(),
                        TextInput::make('quina_max_quadrante_2')
                            ->label('Qtd em Q2 (21 a 40)')
                            ->helperText('Máximo no Quadrante 2 (ex: 3)')
                            ->numeric()
                            ->required(),
                        TextInput::make('quina_max_quadrante_3')
                            ->label('Qtd em Q3 (41 a 60)')
                            ->helperText('Máximo no Quadrante 3 (ex: 3)')
                            ->numeric()
                            ->required(),
                        TextInput::make('quina_max_quadrante_4')
                            ->label('Qtd em Q4 (61 a 80)')
                            ->helperText('Máximo no Quadrante 4 (ex: 3)')
                            ->numeric()
                            ->required(),
                        TextInput::make('quina_max_sub_quadrante')
                            ->label('Qtd por Sub-Quadrante (2x2)')
                            ->helperText('Máximo permitido dentro do mesmo bloco 2x2 (ex: 1)')
                            ->numeric()
                            ->required(),
                        TextInput::make('quina_total_numeros')
                            ->label('Total Padrão de Números no Pool')
                            ->helperText('Quantidade padrão sugerida ao criar novos bolões da Quina')
                            ->numeric()
                            ->required()
                            ->columnSpan(4),
                    ]),

                Section::make('Filtros Inteligentes de Fechamento (Quina)')
                    ->description('Ative ou desative os filtros aplicados pelo motor combinatório de Duques e Ternos.')
                    ->columns(4)
                    ->schema([
                        Toggle::make('quina_filtro_pares_impares')
                            ->label('Pares / Ímpares')
                            ->helperText('Favorece 2P/3I ou 3P/2I.'),
                        Toggle::make('quina_filtro_primos')
                            ->label('Primos')
                            ->helperText('Exige entre 1 e 3 números primos (22 primos no volante).'),
                        Toggle::make('quina_filtro_fibonacci')
                            ->label('Fibonacci')
                            ->helperText('Exige entre 0 e 2 números da sequência.'),
                        Toggle::make('quina_filtro_soma')
                            ->label('Soma das Dezenas')
                            ->helperText('Faixa ideal entre 120 e 280.'),
                    ]),

                Section::make('Valores e Estimativas de Premiação da Quina')
                    ->columns(5)
                    ->schema([
                        TextInput::make('quina_valor_aposta')
                            ->label('Custo da Aposta (R$)')
                            ->numeric()
                            ->prefix('R$')
                            ->required(),
                        TextInput::make('quina_premio_duque')
                            ->label('Estimativa Duque (R$)')
                            ->numeric()
                            ->prefix('R$')
                            ->required(),
                        TextInput::make('quina_premio_terno')
                            ->label('Estimativa Terno (R$)')
                            ->numeric()
                            ->prefix('R$')
                            ->required(),
                        TextInput::make('quina_premio_quadra')
                            ->label('Estimativa Quadra (R$)')
                            ->numeric()
                            ->prefix('R$')
                            ->required(),
                        TextInput::make('quina_premio_quina')
                            ->label('Estimativa Quina (R$)')
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
            ->body('As configurações da Quina foram salvas com sucesso.')
            ->success()
            ->send();
    }
}
