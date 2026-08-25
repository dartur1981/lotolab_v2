<?php

namespace App\Filament\App\Pages\Lotofacil;

use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\ConfiguracaoApp;
use Filament\Forms\Components\Toggle;

class Configuracoes extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Configurações';
    protected ?string $heading = 'Configurações do Sistema';
    protected static string|\UnitEnum|null $navigationGroup = 'Lotofácil';
    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.app.pages.lotofacil.configuracoes';

    public ?array $data = [];

    public function mount(): void
    {
        $configuracoes = ConfiguracaoApp::pluck('valor', 'chave')->toArray();
        
        $this->form->fill([
            'janela_tendencia' => $configuracoes['janela_tendencia'] ?? 15,
            'valor_aposta' => $configuracoes['valor_aposta'] ?? 3.00,
            'premio_11' => $configuracoes['premio_11'] ?? 6.00,
            'premio_12' => $configuracoes['premio_12'] ?? 12.00,
            'premio_13' => $configuracoes['premio_13'] ?? 30.00,
            'premio_14' => $configuracoes['premio_14'] ?? 1500.00,
            'premio_15' => $configuracoes['premio_15'] ?? 1500000.00,
            'filtro_pares_impares' => filter_var($configuracoes['filtro_pares_impares'] ?? '1', FILTER_VALIDATE_BOOLEAN),
            'filtro_primos' => filter_var($configuracoes['filtro_primos'] ?? '1', FILTER_VALIDATE_BOOLEAN),
            'filtro_moldura' => filter_var($configuracoes['filtro_moldura'] ?? '1', FILTER_VALIDATE_BOOLEAN),
            'filtro_posicionais' => filter_var($configuracoes['filtro_posicionais'] ?? '1', FILTER_VALIDATE_BOOLEAN),
            'filtro_casadas' => filter_var($configuracoes['filtro_casadas'] ?? '0', FILTER_VALIDATE_BOOLEAN),
            'filtro_fibonacci' => filter_var($configuracoes['filtro_fibonacci'] ?? '1', FILTER_VALIDATE_BOOLEAN),
            'filtro_multiplos_tres' => filter_var($configuracoes['filtro_multiplos_tres'] ?? '1', FILTER_VALIDATE_BOOLEAN),
            'filtro_sequencias' => filter_var($configuracoes['filtro_sequencias'] ?? '1', FILTER_VALIDATE_BOOLEAN),
            'filtro_soma' => filter_var($configuracoes['filtro_soma'] ?? '1', FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Algoritmo de Tendências')
                    ->description('Configurações matemáticas do backend Python.')
                    ->schema([
                        TextInput::make('janela_tendencia')
                            ->label('Janela de Sorteios (Quentes/Frias)')
                            ->numeric()
                            ->required()
                            ->helperText('O número de sorteios recentes usados para determinar se uma dezena está quente ou fria.'),
                    ]),
                Section::make('Filtros Inteligentes de Fechamento')
                    ->description('Ative ou desative os filtros aplicados pelo motor em Python na geração de novos jogos.')
                    ->columns(3)
                    ->schema([
                        Toggle::make('filtro_pares_impares')
                            ->label('Pares/Ímpares')
                            ->helperText('Exige que o jogo tenha 7 Pares / 8 Ímpares ou 8 Pares / 7 Ímpares.'),
                        Toggle::make('filtro_primos')
                            ->label('Primos')
                            ->helperText('Exige que o jogo tenha entre 4 e 6 números primos.'),
                        Toggle::make('filtro_moldura')
                            ->label('Moldura/Miolo')
                            ->helperText('Exige que o jogo tenha entre 9 e 10 números na moldura.'),
                        Toggle::make('filtro_posicionais')
                            ->label('Limites Posicionais')
                            ->helperText('Valida se a primeira e última dezena do jogo estão dentro dos limites matemáticos comuns.'),
                        Toggle::make('filtro_casadas')
                            ->label('Dezenas Casadas')
                            ->helperText('Prioriza manter dezenas casadas na geração aleatória (Afinidade).'),
                        Toggle::make('filtro_fibonacci')
                            ->label('Fibonacci')
                            ->helperText('Exige que o jogo tenha entre 3 e 5 dezenas da sequência Fibonacci.'),
                        Toggle::make('filtro_multiplos_tres')
                            ->label('Múltiplos de 3')
                            ->helperText('Exige que o jogo tenha entre 4 e 6 múltiplos de três.'),
                        Toggle::make('filtro_sequencias')
                            ->label('Sequências Máximas')
                            ->helperText('Descarta jogos com mais de 7 dezenas seguidas (ex: 1, 2, 3, 4, 5, 6, 7, 8).'),
                        Toggle::make('filtro_soma')
                            ->label('Soma das Dezenas')
                            ->helperText('Exige que a soma das 15 dezenas fique entre 180 e 210 (o padrão histórico).'),
                    ]),
                Section::make('Valores e Premiações')
                    ->description('Valores da aposta e média dos prêmios pagos na Lotofácil.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('valor_aposta')
                            ->label('Custo da Aposta Simples (R$)')
                            ->numeric()
                            ->prefix('R$')
                            ->required(),
                        TextInput::make('premio_11')
                            ->label('Prêmio Fixo - 11 Acertos')
                            ->numeric()
                            ->prefix('R$')
                            ->required(),
                        TextInput::make('premio_12')
                            ->label('Prêmio Fixo - 12 Acertos')
                            ->numeric()
                            ->prefix('R$')
                            ->required(),
                        TextInput::make('premio_13')
                            ->label('Prêmio Fixo - 13 Acertos')
                            ->numeric()
                            ->prefix('R$')
                            ->required(),
                        TextInput::make('premio_14')
                            ->label('Prêmio Médio - 14 Acertos')
                            ->numeric()
                            ->prefix('R$')
                            ->required(),
                        TextInput::make('premio_15')
                            ->label('Prêmio Médio - 15 Acertos')
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
                ->color('primary'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $chave => $valor) {
            // Converte booleanos de volta para '1' ou '0' para armazenar string de forma consistente
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
            ->body('As configurações foram salvas com sucesso.')
            ->success()
            ->send();
    }
}
