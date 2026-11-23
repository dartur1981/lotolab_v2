<?php

namespace App\Livewire\Pwa\Quina;

use Livewire\Component;
use App\Models\Quina\Bolao;
use Illuminate\Support\Facades\DB;
use App\Livewire\Pwa\Traits\HasBolaoAtivoQuina;

class Dashboard extends Component
{
    use HasBolaoAtivoQuina;

    public ?array $bolaoAtivo = null;
    public int $totalNumeros = 0;
    public int $qtdParticipantes = 0;
    public int $porPessoa = 0;
    public int $minhasMarcadasCount = 0;
    public int $limitePessoal = 0;
    public int $apostaramCount = 0;
    public int $progressoPercent = 0;
    public bool $isEncerrado = false;
    public bool $userHasBet = false;
    public array $minhasDezenas = [];
    public array $poolDezenas = [];

    public function mount()
    {
        if (!auth()->check()) {
            return redirect()->to('/pwa/quina/login');
        }

        $this->loadDashboardData();
    }

    public function loadDashboardData()
    {
        $bolao = $this->getBolaoAtivoModel();

        if (!$bolao) return;

        $this->bolaoAtivo = $bolao->toArray();
        $this->totalNumeros = $bolao->total_numeros ?? 5;
        $this->isEncerrado = ($bolao->status == 2);
        
        $pivotRecords = DB::table('quina_bolao_user')
            ->where('quina_bolao_id', $bolao->id)
            ->get();

        $this->qtdParticipantes = count($pivotRecords);
        $this->porPessoa = $this->qtdParticipantes > 0 ? (int)floor($this->totalNumeros / $this->qtdParticipantes) : 0;
        $this->limitePessoal = $bolao->getLimiteDezenasForUser(auth()->id());

        $apostaram = 0;
        $allSelected = [];
        $userId = auth()->id();

        foreach ($pivotRecords as $rec) {
            $nums = json_decode($rec->numeros_selecionados, true) ?? [];
            if ($rec->user_id == $userId) {
                $this->minhasDezenas = $nums;
                $this->minhasMarcadasCount = count($nums);
                $this->userHasBet = ($this->minhasMarcadasCount >= $this->limitePessoal && $this->limitePessoal > 0);
            }
            if (!empty($nums)) {
                $apostaram++;
                $allSelected = array_merge($allSelected, $nums);
            }
        }

        $this->poolDezenas = array_values(array_unique($allSelected));
        sort($this->poolDezenas);
        $this->apostaramCount = $apostaram;

        if ($this->totalNumeros > 0) {
            $this->progressoPercent = (int) min(100, round((count($this->poolDezenas) / $this->totalNumeros) * 100));
        } else {
            $this->progressoPercent = 0;
        }
    }

    public function render()
    {
        return view('livewire.pwa.quina.dashboard')
            ->layout('components.layouts.pwa', [
                'title' => 'Dashboard - Quina Bolão',
                'showHeader' => true,
                'showNav' => true,
                'loteria' => 'quina'
            ]);
    }
}
