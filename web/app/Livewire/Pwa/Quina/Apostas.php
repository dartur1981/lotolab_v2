<?php

namespace App\Livewire\Pwa\Quina;

use Livewire\Component;
use App\Models\Quina\Bolao;
use Illuminate\Support\Facades\DB;
use App\Livewire\Pwa\Traits\HasBolaoAtivoQuina;

class Apostas extends Component
{
    use HasBolaoAtivoQuina;

    public ?array $bolaoAtivo = null;
    public array $participantesApostas = [];
    public array $cartelasGeradas = [];

    public function mount()
    {
        if (!auth()->check()) {
            return redirect()->to('/pwa/quina/login');
        }

        $this->loadApostas();
    }

    public function loadApostas()
    {
        $userId = auth()->id();
        $bolao = $this->getBolaoAtivoModel();

        if (!$bolao) return;

        $this->bolaoAtivo = $bolao->toArray();

        // 1. Apostas de cada participante
        $pivotRecords = DB::table('quina_bolao_user')
            ->join('users', 'quina_bolao_user.user_id', '=', 'users.id')
            ->where('quina_bolao_id', $bolao->id)
            ->select('quina_bolao_user.*', 'users.name as user_name')
            ->orderBy('quina_bolao_user.id')
            ->get();

        $apostas = [];
        foreach ($pivotRecords as $rec) {
            $nums = json_decode($rec->numeros_selecionados, true) ?? [];
            sort($nums);
            $apostas[] = [
                'user_id' => $rec->user_id,
                'name' => $rec->user_name,
                'is_me' => $rec->user_id == $userId,
                'numeros' => $nums,
                'qtd' => count($nums),
            ];
        }

        $this->participantesApostas = $apostas;

        // 2. Jogos gerados do Fechamento (se houver)
        if ($bolao->fechamento && $bolao->fechamento->jogos) {
            $this->cartelasGeradas = $bolao->fechamento->jogos->map(function($jogo) {
                return [
                    'id' => $jogo->id,
                    'dezenas' => $jogo->dezenas,
                    'grupos' => $jogo->grupos,
                    'status' => $jogo->status,
                    'acertos' => $jogo->acertos,
                ];
            })->toArray();
        } else {
            $this->cartelasGeradas = [];
        }
    }

    public function render()
    {
        return view('livewire.pwa.quina.apostas')
            ->layout('components.layouts.pwa', [
                'title' => 'Apostas - Quina Bolão',
                'showHeader' => true,
                'showNav' => true,
                'loteria' => 'quina'
            ]);
    }
}
