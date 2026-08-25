<?php

namespace App\Livewire\Pwa\Lotofacil;

use Livewire\Component;
use App\Livewire\Pwa\Traits\HasBolaoAtivo;

class Resultado extends Component
{
    use HasBolaoAtivo;

    public bool $hasResultado = false;

    public array $resumoResultados = [
        'acertos_15' => 0,
        'acertos_14' => 0,
        'acertos_13' => 0,
        'acertos_12' => 0,
        'acertos_11' => 0,
    ];

    public function mount()
    {
        if (!auth()->check()) {
            return redirect()->to('/pwa/lotofacil/login');
        }
    }

    public function render()
    {
        return view('livewire.pwa.lotofacil.resultado')
            ->layout('components.layouts.pwa', [
                'title' => 'Resultados Lotofácil - LotoBolão',
                'showNav' => true,
                'showHeader' => true
            ]);
    }
}
