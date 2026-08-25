<?php

namespace App\Livewire\Pwa\Lotofacil;

use Livewire\Component;
use App\Models\Lotofacil\Bolao;
use Illuminate\Support\Facades\DB;
use App\Livewire\Pwa\Traits\HasBolaoAtivo;

class Dashboard extends Component
{
    use HasBolaoAtivo;

    public ?array $bolaoAtivo = null;
    public int $totalNumeros = 0;
    public int $qtdParticipantes = 0;
    public int $porPessoa = 0;
    public int $apostaramCount = 0;
    public int $progressoPercent = 0;
    public bool $isEncerrado = false;
    public bool $hasResultado = false;
    public bool $userHasBet = false;

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

        $this->loadDashboardData();
    }

    public function loadDashboardData()
    {
        $bolao = $this->getBolaoAtivoModel();

        if (!$bolao) return;

        $this->bolaoAtivo = $bolao->toArray();
        $this->totalNumeros = $bolao->total_numeros ?? 25;
        
        $pivotRecords = DB::table('lotofacil_bolao_user')
            ->where('lotofacil_bolao_id', $bolao->id)
            ->get();

        $this->qtdParticipantes = count($pivotRecords);
        $this->porPessoa = $this->qtdParticipantes > 0 ? (int)floor($this->totalNumeros / $this->qtdParticipantes) : 0;

        $apostaram = 0;
        $this->userHasBet = false;
        foreach ($pivotRecords as $rec) {
            $nums = json_decode($rec->numeros_selecionados, true) ?? [];
            if (count($nums) > 0) {
                $apostaram++;
                if ($rec->user_id == auth()->id()) {
                    $this->userHasBet = true;
                }
            }
        }

        $this->apostaramCount = $apostaram;
        $this->progressoPercent = $this->qtdParticipantes > 0 ? (int)round(($apostaram / $this->qtdParticipantes) * 100) : 0;
        $this->isEncerrado = $this->progressoPercent >= 100;
        $this->hasResultado = false;
    }

    public function render()
    {
        return view('livewire.pwa.lotofacil.dashboard')
            ->layout('components.layouts.pwa', [
                'title' => 'Dashboard - LotoBolão Lotofácil',
                'showNav' => true,
                'showHeader' => true
            ]);
    }
}
