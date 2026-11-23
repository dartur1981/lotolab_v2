<?php

namespace App\Livewire\Pwa\Quina;

use Livewire\Component;
use App\Models\Quina\Bolao;
use Illuminate\Support\Facades\DB;
use App\Livewire\Pwa\Traits\HasBolaoAtivoQuina;

class Volante extends Component
{
    use HasBolaoAtivoQuina;

    public ?int $bolaoId = null;
    public array $minhasDezenas = [];
    public array $dezenasOutros = [];
    public array $tendencias = [];
    public int $limitePessoal = 0;
    public ?array $bolaoAtivo = null;
    public bool $isLocked = false;

    public array $stats = [
        'q1' => 0,
        'q2' => 0,
        'q3' => 0,
        'q4' => 0,
        'subs' => [],
        'pares' => 0,
        'impares' => 0,
        'soma' => 0,
    ];

    public function mount()
    {
        if (!auth()->check()) {
            return redirect()->to('/pwa/quina/login');
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
            $records = DB::connection('analytics_quina')->select('SELECT dezena, temperatura FROM tendencias_dezenas_quina');
            $this->tendencias = [];
            foreach ($records as $r) {
                $tempRaw = strtolower(trim($r->temperatura ?? ''));
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
        $this->limitePessoal = $bolao->getLimiteDezenasForUser(auth()->id());

        $pivotRecords = DB::table('quina_bolao_user')
            ->where('quina_bolao_id', $this->bolaoId)
            ->orderBy('id')
            ->get();

        $userId = auth()->id();
        $minhas = [];
        $outros = [];

        foreach ($pivotRecords as $r) {
            $nums = json_decode($r->numeros_selecionados, true) ?? [];
            if ($r->user_id == $userId) {
                $minhas = $nums;
            } else {
                $outros = array_merge($outros, $nums);
            }
        }

        $this->minhasDezenas = array_map('intval', $minhas);
        $this->dezenasOutros = array_map('intval', array_unique($outros));

        $totalSelecionadas = count($this->minhasDezenas) + count($this->dezenasOutros);
        $totalBolao = $this->bolaoAtivo['total_numeros'] ?? 80;
        $this->isLocked = ($this->bolaoAtivo['status'] == 2) || ($totalSelecionadas >= $totalBolao);

        $this->calcularStats();
    }

    public function calcularStats()
    {
        $q1 = $q2 = $q3 = $q4 = $pares = $soma = 0;
        // 20 sub-quadrantes no total (5 blocos 2x2 por quadrante x 4 quadrantes = 20)
        $subs = array_fill(1, 20, 0);

        // Estatísticas pessoais (minhas dezenas)
        foreach ($this->minhasDezenas as $d) {
            $soma += $d;
            if ($d % 2 == 0) $pares++;
        }

        // Estatísticas consolidadas do pool do bolão (minhas + outros)
        $todasDezenas = array_unique(array_merge($this->minhasDezenas, $this->dezenasOutros));

        foreach ($todasDezenas as $d) {
            if ($d <= 20) {
                $q1++;
            } elseif ($d <= 40) {
                $q2++;
            } elseif ($d <= 60) {
                $q3++;
            } else {
                $q4++;
            }

            $linha = (int) floor(($d - 1) / 10);
            $coluna = ($d - 1) % 10;
            $subIndex = ((int) floor($linha / 2) * 5) + (int) floor($coluna / 2) + 1;
            if (isset($subs[$subIndex])) {
                $subs[$subIndex]++;
            }
        }

        $this->stats = [
            'q1' => $q1,
            'q2' => $q2,
            'q3' => $q3,
            'q4' => $q4,
            'subs' => $subs,
            'pares' => $pares,
            'impares' => count($this->minhasDezenas) - $pares,
            'soma' => $soma,
        ];
    }

    public function toggleDezena(int $num)
    {
        if (!auth()->check()) {
            return redirect()->to('/pwa/quina/login');
        }

        if (!$this->bolaoId) return;

        if ($this->isLocked) {
            $this->dispatch('toast', message: 'Bolão encerrado! Não é possível alterar dezenas.', type: 'error');
            return;
        }

        // Se já está selecionada por mim, desmarca imediatamente
        if (in_array($num, $this->minhasDezenas)) {
            $this->minhasDezenas = array_values(array_diff($this->minhasDezenas, [$num]));
            $this->salvarSelecoes();
            $this->dispatch('toast', message: 'Dezena ' . sprintf('%02d', $num) . ' desmarcada.', type: 'info');
            return;
        }

        // Recarrega em tempo real para evitar conflito de participantes concorrentes
        $this->refreshBoard();

        if (in_array($num, $this->dezenasOutros)) {
            $this->dispatch('toast', message: 'Dezena ' . sprintf('%02d', $num) . ' já escolhida por outro participante!', type: 'error');
            return;
        }

        // Limite pessoal da cota
        if ($this->limitePessoal > 0 && count($this->minhasDezenas) >= $this->limitePessoal) {
            $this->dispatch('toast', message: "Você já atingiu sua cota de {$this->limitePessoal} dezenas!", type: 'error');
            return;
        }

        // Validação da Quantidade Permitida por Quadrante (Q1, Q2, Q3, Q4)
        if ($num <= 20) {
            $maxQ = (int) ($this->bolaoAtivo['max_quadrante_1'] ?? 3);
            if ($maxQ > 0 && $this->stats['q1'] >= $maxQ) {
                $this->dispatch('toast', message: "Limite atingido: máximo de {$maxQ} número(s) no Quadrante 1 (01 a 20).", type: 'error');
                return;
            }
        } elseif ($num <= 40) {
            $maxQ = (int) ($this->bolaoAtivo['max_quadrante_2'] ?? 3);
            if ($maxQ > 0 && $this->stats['q2'] >= $maxQ) {
                $this->dispatch('toast', message: "Limite atingido: máximo de {$maxQ} número(s) no Quadrante 2 (21 a 40).", type: 'error');
                return;
            }
        } elseif ($num <= 60) {
            $maxQ = (int) ($this->bolaoAtivo['max_quadrante_3'] ?? 3);
            if ($maxQ > 0 && $this->stats['q3'] >= $maxQ) {
                $this->dispatch('toast', message: "Limite atingido: máximo de {$maxQ} número(s) no Quadrante 3 (41 a 60).", type: 'error');
                return;
            }
        } else {
            $maxQ = (int) ($this->bolaoAtivo['max_quadrante_4'] ?? 3);
            if ($maxQ > 0 && $this->stats['q4'] >= $maxQ) {
                $this->dispatch('toast', message: "Limite atingido: máximo de {$maxQ} número(s) no Quadrante 4 (61 a 80).", type: 'error');
                return;
            }
        }

        // Validação da Quantidade Permitida por Sub-Quadrante (Bloco 2x2 do volante)
        $linha = (int) floor(($num - 1) / 10);
        $coluna = ($num - 1) % 10;
        $subIndex = ((int) floor($linha / 2) * 5) + (int) floor($coluna / 2) + 1;
        $maxSub = (int) ($this->bolaoAtivo['max_sub_quadrante'] ?? 1);

        if ($maxSub > 0 && ($this->stats['subs'][$subIndex] ?? 0) >= $maxSub) {
            $this->dispatch('toast', message: "Limite atingido: o Sub-Quadrante {$subIndex} já possui o máximo de {$maxSub} número(s) no bolão.", type: 'error');
            return;
        }

        // Adiciona dezena
        $this->minhasDezenas[] = $num;
        sort($this->minhasDezenas);
        $this->salvarSelecoes();
        $this->dispatch('toast', message: 'Dezena ' . sprintf('%02d', $num) . ' marcada com sucesso!', type: 'success');
    }

    public function salvarSelecoes()
    {
        $userId = auth()->id();
        sort($this->minhasDezenas);

        DB::table('quina_bolao_user')
            ->where('quina_bolao_id', $this->bolaoId)
            ->where('user_id', $userId)
            ->update([
                'numeros_selecionados' => json_encode($this->minhasDezenas),
                'updated_at' => now(),
            ]);

        $this->calcularStats();
        $this->verificarConclusaoBolao();
    }

    public function verificarConclusaoBolao()
    {
        $registros = DB::table('quina_bolao_user')
            ->where('quina_bolao_id', $this->bolaoId)
            ->get();

        $totalMarcadas = 0;
        foreach ($registros as $r) {
            $selecionadas = json_decode($r->numeros_selecionados, true) ?? [];
            $totalMarcadas += count($selecionadas);
        }

        $totalBolao = $this->bolaoAtivo['total_numeros'] ?? 80;
        if ($totalMarcadas >= $totalBolao) {
            Bolao::where('id', $this->bolaoId)->update(['status' => 2]);
            $this->bolaoAtivo['status'] = 2;
            $this->isLocked = true;
            $this->dispatch('toast', message: 'Bolão completo e encerrado!', type: 'success');
        }
    }

    public function render()
    {
        $maxQ1 = (int) ($this->bolaoAtivo['max_quadrante_1'] ?? 3);
        $maxQ2 = (int) ($this->bolaoAtivo['max_quadrante_2'] ?? 3);
        $maxQ3 = (int) ($this->bolaoAtivo['max_quadrante_3'] ?? 3);
        $maxQ4 = (int) ($this->bolaoAtivo['max_quadrante_4'] ?? 3);
        $maxSub = (int) ($this->bolaoAtivo['max_sub_quadrante'] ?? 1);

        $quadrantesDef = [
            [
                'id' => 1,
                'label' => 'Quadrante 1 (01 a 20)',
                'count' => $this->stats['q1'],
                'max' => $maxQ1,
                'sub_start' => 1,
                'sub_end' => 5,
            ],
            [
                'id' => 2,
                'label' => 'Quadrante 2 (21 a 40)',
                'count' => $this->stats['q2'],
                'max' => $maxQ2,
                'sub_start' => 6,
                'sub_end' => 10,
            ],
            [
                'id' => 3,
                'label' => 'Quadrante 3 (41 a 60)',
                'count' => $this->stats['q3'],
                'max' => $maxQ3,
                'sub_start' => 11,
                'sub_end' => 15,
            ],
            [
                'id' => 4,
                'label' => 'Quadrante 4 (61 a 80)',
                'count' => $this->stats['q4'],
                'max' => $maxQ4,
                'sub_start' => 16,
                'sub_end' => 20,
            ],
        ];

        return view('livewire.pwa.quina.volante', [
            'quadrantesDef' => $quadrantesDef,
            'maxSub' => $maxSub,
        ])->layout('components.layouts.pwa', [
            'title' => 'Volante Quina - LotoLab',
            'showNav' => true,
            'showHeader' => true,
            'loteria' => 'quina'
        ]);
    }
}
