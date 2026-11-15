<?php

namespace App\Livewire;

use Filament\Widgets\Widget;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;

class MegasenaCiclosWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    public static function canView(): bool
    {
        return Filament::getTenant()?->slug === 'megasena';
    }

    protected string $view = 'livewire.megasena-ciclos-widget';
    protected int | string | array $columnSpan = 'full';

    public $ciclos = [];
    public ?int $selectedCiclo = null;
    public $fileiras = [];

    public function mount()
    {
        $this->carregarListaCiclos();
        
        if ($this->ciclos && $this->ciclos->isNotEmpty()) {
            $primeiro = $this->ciclos->first();
            if ($primeiro) {
                $this->selectedCiclo = $primeiro->numero_ciclo;
                $this->form->fill(['selectedCiclo' => $this->selectedCiclo]);
                $this->carregarCicloData();
            }
        }
    }

    public function form(Schema $form): Schema
    {
        $opcoes = [];
        if ($this->ciclos) {
            foreach ($this->ciclos as $c) {
                $opcoes[$c->numero_ciclo] = "Ciclo {$c->numero_ciclo} — {$c->status}";
            }
        }

        return $form
            ->schema([
                Select::make('selectedCiclo')
                    ->label('Selecione o Ciclo da Mega-Sena:')
                    ->options($opcoes)
                    ->placeholder(empty($opcoes) ? 'Nenhum ciclo cadastrado ainda' : 'Selecione um ciclo')
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function () {
                        $this->carregarCicloData();
                    })
            ]);
    }

    public function updatedSelectedCiclo()
    {
        $this->carregarCicloData();
    }

    private function carregarListaCiclos()
    {
        try {
            if (\Illuminate\Support\Facades\Schema::connection('analytics_megasena')->hasTable('ciclos_megasena')) {
                $this->ciclos = DB::connection('analytics_megasena')->table('ciclos_megasena')
                    ->orderByDesc('numero_ciclo')
                    ->get();
            } else {
                $this->ciclos = collect();
            }
        } catch (\Throwable $e) {
            $this->ciclos = collect();
        }
    }

    private function carregarCicloData()
    {
        $this->fileiras = [];

        if (!$this->selectedCiclo) return;

        $ciclo = DB::connection('analytics_megasena')->table('ciclos_megasena')->where('numero_ciclo', $this->selectedCiclo)->first();

        if (!$ciclo || !$ciclo->json_matriz) return;

        $dados = json_decode($ciclo->json_matriz, true);
        
        if (isset($dados['matriz']) && is_array($dados['matriz'])) {
            $matrizCronologica = array_reverse($dados['matriz']);
            
            $acumuladoGlobal = [];
            $dezenasAnteriores = [];

            $primeiroConcurso = $matrizCronologica[0]['concurso'] ?? null;
            if ($primeiroConcurso) {
                $sorteioAnterior = DB::connection('analytics_megasena')
                    ->table('resultados_megasena')
                    ->where('concurso', $primeiroConcurso - 1)
                    ->first();
                
                if ($sorteioAnterior) {
                    for ($i = 1; $i <= 6; $i++) {
                        $col = 'bola_' . $i;
                        if (isset($sorteioAnterior->$col)) {
                            $dezenasAnteriores[] = $sorteioAnterior->$col;
                        }
                    }
                }
            }
            
            foreach ($matrizCronologica as $sorteio) {
                $dezenasDoSorteio = $sorteio['dezenas'] ?? [];
                
                $acumuladoGlobal = array_unique(array_merge($acumuladoGlobal, $dezenasDoSorteio));
                sort($acumuladoGlobal);
                
                $this->fileiras[] = [
                    'concurso' => $sorteio['concurso'],
                    'acumulado' => $acumuladoGlobal,
                    'dezenas_sorteio' => $dezenasDoSorteio,
                    'dezenas_anteriores' => $dezenasAnteriores
                ];
                
                $dezenasAnteriores = $dezenasDoSorteio;
            }
        }
    }
}
