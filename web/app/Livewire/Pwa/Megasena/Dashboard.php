<?php

namespace App\Livewire\Pwa\Megasena;

use Livewire\Component;
use App\Models\Megasena\Bolao;
use Illuminate\Support\Facades\DB;
use App\Livewire\Pwa\Traits\HasBolaoAtivoMegasena;

class Dashboard extends Component
{
    use HasBolaoAtivoMegasena;

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
            return redirect()->to('/pwa/megasena/login');
        }

        $this->loadDashboardData();
    }

    public function loadDashboardData()
    {
        $bolao = $this->getBolaoAtivoModel();

        if (!$bolao) return;

        $this->bolaoAtivo = $bolao->toArray();
        $this->totalNumeros = $bolao->total_numeros ?? 6;
        $this->isEncerrado = ($bolao->status == 2);
        
        $pivotRecords = DB::table('megasena_bolao_user')
            ->where('megasena_bolao_id', $bolao->id)
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

        $this->apostaramCount = $apostaram;
        $this->poolDezenas = array_values(array_unique($allSelected));
        sort($this->poolDezenas);

        $totalUnique = count($this->poolDezenas);
        $this->progressoPercent = $this->totalNumeros > 0 
            ? min(100, (int)round(($totalUnique / $this->totalNumeros) * 100)) 
            : 0;
    }

    public function render()
    {
        return view('livewire.pwa.megasena.dashboard')
            ->layout('components.layouts.pwa', [
                'title' => 'Dashboard Mega-Sena - LotoLab',
                'showNav' => true,
                'showHeader' => true,
                'loteria' => 'megasena'
            ]);
    }
}
