<?php

namespace App\Livewire;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;

class CiclosWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'livewire.ciclos-widget';
    protected int | string | array $columnSpan = 'full';

    public $ciclos = [];
    public ?int $selectedCiclo = null;
    public $fileiras = [];

    public function mount()
    {
        $this->carregarListaCiclos();
        
        if (!empty($this->ciclos)) {
            // Seleciona o ciclo aberto ou o mais recente
            $this->selectedCiclo = $this->ciclos[0]->numero_ciclo;
            $this->form->fill(['selectedCiclo' => $this->selectedCiclo]);
            $this->carregarCicloData();
        }
    }

    public function form(Schema $form): Schema
    {
        $opcoes = [];
        foreach ($this->ciclos as $c) {
            $opcoes[$c->numero_ciclo] = "Ciclo {$c->numero_ciclo} — {$c->status}";
        }

        return $form
            ->schema([
                Select::make('selectedCiclo')
                    ->label('Selecione o Ciclo para Análise:')
                    ->options($opcoes)
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
        $this->ciclos = DB::connection('analytics_lotofacil')->table('ciclos_lotofacil')
            ->orderByDesc('numero_ciclo')
            ->get();
    }

    private function carregarCicloData()
    {
        $this->fileiras = [];

        if (!$this->selectedCiclo) return;

        $ciclo = DB::connection('analytics_lotofacil')->table('ciclos_lotofacil')->where('numero_ciclo', $this->selectedCiclo)->first();

        if (!$ciclo || !$ciclo->json_matriz) return;

        $dados = json_decode($ciclo->json_matriz, true);
        
        if (isset($dados['matriz']) && is_array($dados['matriz'])) {
            // A matriz original no python é gerada decrescente (insere no topo). 
            // Para exibição cronológica, revertemos o array.
            $matrizCronologica = array_reverse($dados['matriz']);
            
            $acumuladoGlobal = [];
            
            foreach ($matrizCronologica as $sorteio) {
                $dezenasDoSorteio = $sorteio['dezenas'] ?? [];
                
                // Mescla as dezenas atuais com as anteriores para o acumulado
                $acumuladoGlobal = array_unique(array_merge($acumuladoGlobal, $dezenasDoSorteio));
                sort($acumuladoGlobal);
                
                $this->fileiras[] = [
                    'concurso' => $sorteio['concurso'],
                    'acumulado' => $acumuladoGlobal,
                    'dezenas_sorteio' => $dezenasDoSorteio
                ];
            }
        }
    }
}
