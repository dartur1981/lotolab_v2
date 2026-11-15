<?php

namespace App\Livewire\Pwa\Megasena;

use Livewire\Component;
use App\Models\Megasena\Bolao;
use Illuminate\Support\Facades\DB;
use App\Livewire\Pwa\Traits\HasBolaoAtivoMegasena;

class Volante extends Component
{
    use HasBolaoAtivoMegasena;

    public ?int $bolaoId = null;
    public array $minhasDezenas = [];
    public array $dezenasOutros = [];
    public array $tendencias = [];
    public int $limitePessoal = 0;
    public ?array $bolaoAtivo = null;
    public bool $isLocked = false;

    public function mount()
    {
        if (!auth()->check()) {
            return redirect()->to('/pwa/megasena/login');
        }

        $id = $this->getBolaoAtivoId();
        if ($id) {
            $this->bolaoId = (int)$id;
            $bolao = $this->getBolaoAtivoModel();
            $this->bolaoAtivo = $bolao ? $bolao->toArray() : null;
            $this->loadTendencias();
            $this->refreshBoard();
        }
    }

    public function loadTendencias()
    {
        try {
            $records = DB::connection('analytics_megasena')->select('SELECT dezena, temperatura FROM tendencias_dezenas_megasena');
            $this->tendencias = [];
            foreach ($records as $r) {
                $tempRaw = strtolower(trim($r->temperatura));
                $temp = match($tempRaw) {
                    'quente' => 'quente',
                    'morna', 'morno' => 'morno',
                    default => 'frio',
                };
                $this->tendencias[(int)$r->dezena] = $temp;
            }
        } catch (\Exception $e) {
            $this->tendencias = [];
        }
    }

    public function refreshBoard()
    {
        if (!$this->bolaoId) return;

        $bolao = Bolao::find($this->bolaoId);
        if (!$bolao) return;
        $this->bolaoAtivo = $bolao->toArray();

        $userId = auth()->id();

        $pivotRecords = DB::table('megasena_bolao_user')
            ->where('megasena_bolao_id', $this->bolaoId)
            ->orderBy('id')
            ->get();

        $minhas = [];
        $outros = [];
        $this->limitePessoal = $bolao->getLimiteDezenasForUser($userId);

        foreach ($pivotRecords as $record) {
            $selecionadas = json_decode($record->numeros_selecionados, true) ?? [];
            if ($record->user_id == $userId) {
                $minhas = $selecionadas;
            } else {
                $outros = array_merge($outros, $selecionadas);
            }
        }

        $this->minhasDezenas = array_map('intval', $minhas);
        $this->dezenasOutros = array_map('intval', array_unique($outros));

        $totalSelecionadas = count($this->minhasDezenas) + count($this->dezenasOutros);
        $totalBolao = $this->bolaoAtivo['total_numeros'] ?? 60;
        $this->isLocked = ($this->bolaoAtivo['status'] == 2) || ($totalSelecionadas >= $totalBolao);
    }

    public function toggleDezena(int $num)
    {
        if (!auth()->check()) {
            return redirect()->to('/pwa/megasena/login');
        }

        if (!$this->bolaoId) return;

        if ($this->isLocked) {
            $this->dispatch('toast', message: 'Bolão encerrado! Não é possível alterar dezenas.', type: 'error');
            return;
        }

        if (in_array($num, $this->dezenasOutros)) {
            $this->dispatch('toast', message: 'Dezena já escolhida por outro participante!', type: 'error');
            return;
        }

        // Se já está selecionada por mim, desmarca
        if (in_array($num, $this->minhasDezenas)) {
            $this->minhasDezenas = array_values(array_diff($this->minhasDezenas, [$num]));
            $this->salvarSelecoes();
            return;
        }

        // Limite pessoal
        if ($this->limitePessoal > 0 && count($this->minhasDezenas) >= $this->limitePessoal) {
            $this->dispatch('toast', message: "Você já atingiu sua cota de {$this->limitePessoal} dezenas!", type: 'error');
            return;
        }

        // Validação de Quadrante Global do Bolão (Q1, Q2, Q3)
        $todasDezenasComNova = array_merge($this->minhasDezenas, $this->dezenasOutros, [$num]);
        
        $q1Count = count(array_filter($todasDezenasComNova, fn($d) => $d <= 20));
        $q2Count = count(array_filter($todasDezenasComNova, fn($d) => $d >= 21 && $d <= 40));
        $q3Count = count(array_filter($todasDezenasComNova, fn($d) => $d >= 41 && $d <= 60));

        if ($num <= 20 && isset($this->bolaoAtivo['max_quadrante_1']) && $q1Count > (int)$this->bolaoAtivo['max_quadrante_1']) {
            $this->dispatch('toast', message: "Limite do Quadrante 1 (01 a 20) atingido no bolão!", type: 'error');
            return;
        }

        if ($num >= 21 && $num <= 40 && isset($this->bolaoAtivo['max_quadrante_2']) && $q2Count > (int)$this->bolaoAtivo['max_quadrante_2']) {
            $this->dispatch('toast', message: "Limite do Quadrante 2 (21 a 40) atingido no bolão!", type: 'error');
            return;
        }

        if ($num >= 41 && isset($this->bolaoAtivo['max_quadrante_3']) && $q3Count > (int)$this->bolaoAtivo['max_quadrante_3']) {
            $this->dispatch('toast', message: "Limite do Quadrante 3 (41 a 60) atingido no bolão!", type: 'error');
            return;
        }

        // Adiciona dezena
        $this->minhasDezenas[] = $num;
        sort($this->minhasDezenas);
        $this->salvarSelecoes();
    }

    public function salvarSelecoes()
    {
        $userId = auth()->id();
        sort($this->minhasDezenas);

        DB::table('megasena_bolao_user')
            ->where('megasena_bolao_id', $this->bolaoId)
            ->where('user_id', $userId)
            ->update([
                'numeros_selecionados' => json_encode($this->minhasDezenas),
                'updated_at' => now(),
            ]);

        $this->dispatch('toast', message: 'Dezena salva com sucesso!', type: 'success');
    }

    public function render()
    {
        $todas = array_merge($this->minhasDezenas, $this->dezenasOutros);
        $q1 = count(array_filter($todas, fn($d) => $d <= 20));
        $q2 = count(array_filter($todas, fn($d) => $d >= 21 && $d <= 40));
        $q3 = count(array_filter($todas, fn($d) => $d >= 41 && $d <= 60));

        return view('livewire.pwa.megasena.volante', [
            'q1Count' => $q1,
            'q2Count' => $q2,
            'q3Count' => $q3,
        ])->layout('components.layouts.pwa', [
            'title' => 'Volante Mega-Sena - LotoLab',
            'showNav' => true,
            'showHeader' => true,
            'loteria' => 'megasena'
        ]);
    }
}
