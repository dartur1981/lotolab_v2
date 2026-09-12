<?php

namespace App\Livewire\Pwa\Lotofacil;

use Livewire\Component;
use App\Models\Lotofacil\Bolao;
use Illuminate\Support\Facades\DB;
use App\Events\DezenaSelecionadaEvent;
use App\Livewire\Pwa\Traits\HasBolaoAtivo;

class Volante extends Component
{
    use HasBolaoAtivo;

    public ?int $bolaoId = null;
    public array $minhasDezenas = [];
    public array $dezenasOutros = [];
    public array $tendencias = [];
    public int $limitePessoal = 0;
    public ?array $bolaoAtivo = null;
    public bool $isLocked = false;
    public ?array $sugestaoAtual = null;

    public array $dezenasMoldura = [1, 2, 3, 4, 5, 6, 10, 11, 15, 16, 20, 21, 22, 23, 24, 25];
    public array $dezenasMiolo = [7, 8, 9, 12, 13, 14, 17, 18, 19];

    public function mount()
    {
        if (!auth()->check()) {
            return redirect()->to('/pwa/lotofacil/login');
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
            $records = DB::connection('mariadb')->select('SELECT dezena, temperatura FROM lotolab_lotofacil_analytics_v2.tendencias_dezenas');
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

        $pivotRecords = DB::table('lotofacil_bolao_user')
            ->join('users', 'lotofacil_bolao_user.user_id', '=', 'users.id')
            ->where('lotofacil_bolao_id', $this->bolaoId)
            ->select('lotofacil_bolao_user.*', 'users.name as user_name')
            ->orderBy('lotofacil_bolao_user.id')
            ->get();

        $minhas = [];
        $outros = [];
        $usersIds = $pivotRecords->pluck('user_id')->toArray();
        $qtd = count($usersIds);
        $totalBolao = $this->bolaoAtivo['total_numeros'] ?? 25;
        $porPessoa = $qtd > 0 ? (int)floor($totalBolao / $qtd) : 0;
        $sobra = $qtd > 0 ? (int)($totalBolao % $qtd) : 0;
        $ultimoUser = end($usersIds);

        foreach ($pivotRecords as $record) {
            $selecionadas = json_decode($record->numeros_selecionados, true) ?? [];
            if ($record->user_id == $userId) {
                $minhas = $selecionadas;
                $this->limitePessoal = ($record->user_id == $ultimoUser) ? ($porPessoa + $sobra) : $porPessoa;
            } else {
                $outros = array_merge($outros, $selecionadas);
            }
        }

        $this->minhasDezenas = array_map('intval', $minhas);
        $this->dezenasOutros = array_map('intval', array_unique($outros));

        $totalSelecionadas = count($this->minhasDezenas) + count($this->dezenasOutros);
        $this->isLocked = ($this->bolaoAtivo['status'] == 2) || ($totalSelecionadas >= $totalBolao);

        if ($totalSelecionadas >= $totalBolao && $this->bolaoAtivo['status'] != 2) {
            Bolao::where('id', $this->bolaoId)->update(['status' => 2]);
            $this->bolaoAtivo['status'] = 2;
            $this->isLocked = true;
        }
    }

    public function toggleDezena(int $num)
    {
        if (!auth()->check()) {
            return redirect()->to('/pwa/lotofacil/login');
        }

        if (!$this->bolaoId) return;

        if ($this->isLocked) {
            $this->dispatch('toast', message: 'Bolão encerrado! Não é possível alterar dezenas.', type: 'error');
            return;
        }

        $userId = auth()->id();

        if (in_array($num, $this->dezenasOutros)) {
            $this->dispatch('toast', message: 'Dezena já escolhida por outro participante!', type: 'error');
            return;
        }

        if (in_array($num, $this->minhasDezenas)) {
            $this->minhasDezenas = array_values(array_diff($this->minhasDezenas, [$num]));
            $this->sugestaoAtual = null;
            $this->saveAndBroadcast();
            return;
        }

        if ($this->limitePessoal > 0 && count($this->minhasDezenas) >= $this->limitePessoal) {
            $this->dispatch('toast', message: "Você já atingiu seu limite de {$this->limitePessoal} dezenas!", type: 'error');
            return;
        }

        $tempStats = $this->simularEstatisticaAdicionando($num);

        if ($tempStats['moldura'] > ($this->bolaoAtivo['max_moldura'] ?? 16)) {
            $this->dispatch('toast', message: "Limite Global da Moldura foi atingido!", type: 'error');
            return;
        }
        if ($tempStats['miolo'] > ($this->bolaoAtivo['max_miolo'] ?? 9)) {
            $this->dispatch('toast', message: "Limite Global do Miolo foi atingido!", type: 'error');
            return;
        }
        foreach ($tempStats['linhas'] as $l => $count) {
            if ($count > ($this->bolaoAtivo['max_linha'] ?? 5)) {
                $this->dispatch('toast', message: "Limite na linha {$l} atingido!", type: 'error');
                return;
            }
        }
        foreach ($tempStats['colunas'] as $c => $count) {
            if ($count > ($this->bolaoAtivo['max_coluna'] ?? 5)) {
                $this->dispatch('toast', message: "Limite na coluna {$c} atingido!", type: 'error');
                return;
            }
        }

        $this->minhasDezenas[] = $num;
        $this->saveAndBroadcast();
        $this->buscarSugestaoCasada($num);
    }

    public function aceitarSugestao()
    {
        if (!$this->sugestaoAtual) return;
        $num = $this->sugestaoAtual['sugerida'];
        $this->toggleDezena($num);
    }

    private function buscarSugestaoCasada(int $num)
    {
        $this->sugestaoAtual = null;

        if ($this->isLocked) return;
        if ($this->limitePessoal > 0 && count($this->minhasDezenas) >= $this->limitePessoal) return;

        try {
            $candidatas = DB::connection('mariadb')
                ->table('lotolab_lotofacil_analytics_v2.lotofacil_estatisticas_duplas')
                ->where('dezena_1', $num)
                ->orderBy('frequencia', 'desc')
                ->limit(20)
                ->pluck('dezena_2');

            $todasMarcadas = array_unique(array_merge($this->minhasDezenas, $this->dezenasOutros));

            foreach ($candidatas as $candidata) {
                if (in_array($candidata, $todasMarcadas)) continue;

                $tempStats = $this->simularEstatisticaAdicionando($candidata);

                $passou = true;
                if ($tempStats['moldura'] > ($this->bolaoAtivo['max_moldura'] ?? 16)) $passou = false;
                if ($tempStats['miolo'] > ($this->bolaoAtivo['max_miolo'] ?? 9)) $passou = false;
                foreach ($tempStats['linhas'] as $count) {
                    if ($count > ($this->bolaoAtivo['max_linha'] ?? 5)) $passou = false;
                }
                foreach ($tempStats['colunas'] as $count) {
                    if ($count > ($this->bolaoAtivo['max_coluna'] ?? 5)) $passou = false;
                }

                if ($passou) {
                    $this->sugestaoAtual = [
                        'marcada' => $num,
                        'sugerida' => $candidata
                    ];
                    break;
                }
            }
        } catch (\Exception $e) {
            // Ignora falhas de banco analytics
        }
    }

    private function simularEstatisticaAdicionando(int $num): array
    {
        $todas = array_unique(array_merge($this->minhasDezenas, $this->dezenasOutros, [$num]));
        $stats = [
            'moldura' => 0,
            'miolo' => 0,
            'linhas' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
            'colunas' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
        ];

        foreach ($todas as $n) {
            if (in_array($n, $this->dezenasMoldura)) $stats['moldura']++;
            else $stats['miolo']++;

            $stats['linhas'][(int)ceil($n / 5)]++;
            $coluna = $n % 5;
            if ($coluna === 0) $coluna = 5;
            $stats['colunas'][$coluna]++;
        }

        return $stats;
    }

    private function saveAndBroadcast()
    {
        $userId = auth()->id();
        DB::table('lotofacil_bolao_user')
            ->where('lotofacil_bolao_id', $this->bolaoId)
            ->where('user_id', $userId)
            ->update([
                'numeros_selecionados' => json_encode($this->minhasDezenas)
            ]);

        try {
            broadcast(new DezenaSelecionadaEvent(
                $this->bolaoId,
                $userId,
                auth()->user()->name,
                $this->minhasDezenas
            ))->toOthers();
        } catch (\Throwable $e) {
            // Ignora erro se Reverb não estiver ativo
        }

        $this->refreshBoard();
        $this->verificarEncerramento();
    }

    private function verificarEncerramento()
    {
        if (!$this->bolaoId || !$this->bolaoAtivo) return;

        // Se já estiver encerrado, não faz nada
        if (($this->bolaoAtivo['status'] ?? 0) == 2) {
            return;
        }

        $todasMarcadas = array_unique(array_merge($this->minhasDezenas, $this->dezenasOutros));
        $totalBolao = $this->bolaoAtivo['total_numeros'] ?? 25;

        if (count($todasMarcadas) >= $totalBolao) {
            $bolao = Bolao::find($this->bolaoId);
            if ($bolao && $bolao->status != 2) {
                $bolao->status = 2;
                $bolao->save();

                // Gera os fechamentos
                try {
                    $fechamentoService = new \App\Services\LotofacilFechamentoService();
                    $fechamentoService->gerarFechamento($bolao->id);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Erro ao gerar fechamento para o bolão {$bolao->id}: " . $e->getMessage());
                }
            }
        }
    }

    public function render()
    {
        $todasMarcadas = array_unique(array_merge($this->minhasDezenas, $this->dezenasOutros));
        $countMoldura = count(array_intersect($todasMarcadas, $this->dezenasMoldura));
        $countMiolo = count(array_intersect($todasMarcadas, $this->dezenasMiolo));

        return view('livewire.pwa.lotofacil.volante', [
            'countMoldura' => $countMoldura,
            'countMiolo' => $countMiolo,
            'maxMoldura' => $this->bolaoAtivo['max_moldura'] ?? 16,
            'maxMiolo' => $this->bolaoAtivo['max_miolo'] ?? 9,
        ])->layout('components.layouts.pwa', [
            'title' => 'Volante Lotofácil PWA',
            'showHeader' => true
        ]);
    }
}
