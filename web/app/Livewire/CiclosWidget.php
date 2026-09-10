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
        
        if ($this->ciclos && $this->ciclos->isNotEmpty()) {
            // Seleciona o ciclo aberto ou o mais recente
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
                    ->label('Selecione o Ciclo para Análise:')
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
            if (\Illuminate\Support\Facades\Schema::connection('analytics_lotofacil')->hasTable('ciclos_lotofacil')) {
                $this->ciclos = DB::connection('analytics_lotofacil')->table('ciclos_lotofacil')
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

        $ciclo = DB::connection('analytics_lotofacil')->table('ciclos_lotofacil')->where('numero_ciclo', $this->selectedCiclo)->first();

        if (!$ciclo || !$ciclo->json_matriz) return;

        $dados = json_decode($ciclo->json_matriz, true);
        
        if (isset($dados['matriz']) && is_array($dados['matriz'])) {
            // A matriz original no python é gerada decrescente (insere no topo). 
            // Para exibição cronológica, revertemos o array.
            $matrizCronologica = array_reverse($dados['matriz']);
            
            $acumuladoGlobal = [];
            $dezenasAnteriores = [];

            // Tentativa de obter as dezenas do concurso anterior ao primeiro do ciclo
            $primeiroConcurso = $matrizCronologica[0]['concurso'] ?? null;
            if ($primeiroConcurso) {
                $sorteioAnterior = DB::connection('analytics_lotofacil')
                    ->table('resultados_lotofacil')
                    ->where('concurso', $primeiroConcurso - 1)
                    ->first();
                
                if ($sorteioAnterior) {
                    for ($i = 1; $i <= 15; $i++) {
                        $col = 'bola_' . $i;
                        if (isset($sorteioAnterior->$col)) {
                            $dezenasAnteriores[] = $sorteioAnterior->$col;
                        }
                    }
                }
            }
            
            foreach ($matrizCronologica as $sorteio) {
                $dezenasDoSorteio = $sorteio['dezenas'] ?? [];
                
                // Mescla as dezenas atuais com as anteriores para o acumulado
                $acumuladoGlobal = array_unique(array_merge($acumuladoGlobal, $dezenasDoSorteio));
                sort($acumuladoGlobal);
                
                $this->fileiras[] = [
                    'concurso' => $sorteio['concurso'],
                    'acumulado' => $acumuladoGlobal,
                    'dezenas_sorteio' => $dezenasDoSorteio,
                    'dezenas_anteriores' => $dezenasAnteriores
                ];
                
                // Atualiza dezenasAnteriores para o próximo concurso do loop
                $dezenasAnteriores = $dezenasDoSorteio;
            }
        }
    }
}
