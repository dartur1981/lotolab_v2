<?php

namespace App\Livewire\Pwa\Megasena;

use Livewire\Component;
use App\Models\Megasena\Bolao;
use Illuminate\Support\Facades\DB;
use App\Livewire\Pwa\Traits\HasBolaoAtivoMegasena;

class Apostas extends Component
{
    use HasBolaoAtivoMegasena;

    public ?array $bolaoAtivo = null;
    public array $participantesApostas = [];
    public array $cartelasGeradas = [];

    public function mount()
    {
        if (!auth()->check()) {
            return redirect()->to('/pwa/megasena/login');
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
        $pivotRecords = DB::table('megasena_bolao_user')
            ->join('users', 'megasena_bolao_user.user_id', '=', 'users.id')
            ->where('megasena_bolao_id', $bolao->id)
            ->select('megasena_bolao_user.*', 'users.name as user_name')
            ->orderBy('megasena_bolao_user.id')
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
            $this->cartelasGeradas = $bolao->fechamento->jogos->map(function ($jogo) {
                $dezenas = is_string($jogo->dezenas) ? json_decode($jogo->dezenas, true) : $jogo->dezenas;
                sort($dezenas);
                return [
                    'id' => $jogo->id,
                    'dezenas' => $dezenas,
                    'acertos' => $jogo->acertos ?? 0,
                ];
            })->toArray();
        }
    }

    public function render()
    {
        return view('livewire.pwa.megasena.apostas')
            ->layout('components.layouts.pwa', [
                'title' => 'Apostas Mega-Sena - LotoLab',
                'showNav' => true,
                'showHeader' => true,
                'loteria' => 'megasena'
            ]);
    }
}
