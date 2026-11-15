<?php

namespace App\Livewire\Pwa\Megasena;

use Livewire\Component;
use App\Livewire\Pwa\Traits\HasBolaoAtivoMegasena;
use Illuminate\Support\Facades\DB;

class Resultado extends Component
{
    use HasBolaoAtivoMegasena;

    public ?array $ultimoSorteio = null;

    public function mount()
    {
        if (!auth()->check()) {
            return redirect()->to('/pwa/megasena/login');
        }

        $this->loadResultado();
    }

    public function loadResultado()
    {
        try {
            $resultado = DB::connection('analytics_megasena')
                ->table('resultados_megasena')
                ->where('concurso', '!=', 9999)
                ->orderBy('concurso', 'desc')
                ->first();

            if ($resultado) {
                $this->ultimoSorteio = [
                    'concurso' => $resultado->concurso,
                    'data' => date('d/m/Y', strtotime($resultado->data_sorteio)),
                    'dezenas' => [
                        $resultado->bola_1,
                        $resultado->bola_2,
                        $resultado->bola_3,
                        $resultado->bola_4,
                        $resultado->bola_5,
                        $resultado->bola_6,
                    ]
                ];
            }
        } catch (\Exception $e) {
            $this->ultimoSorteio = null;
        }
    }

    public function render()
    {
        return view('livewire.pwa.megasena.resultado')
            ->layout('components.layouts.pwa', [
                'title' => 'Resultados Mega-Sena - LotoLab',
                'showNav' => true,
                'showHeader' => true,
                'loteria' => 'megasena'
            ]);
    }
}
