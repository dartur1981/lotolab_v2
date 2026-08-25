<?php

namespace App\Livewire\Pwa\Lotofacil;

use Livewire\Component;
use App\Models\Lotofacil\Bolao;
use Illuminate\Support\Facades\DB;
use App\Livewire\Pwa\Traits\HasBolaoAtivo;

class Apostas extends Component
{
    use HasBolaoAtivo;

    public ?array $bolaoAtivo = null;
    public array $participantesApostas = [];

    public function mount()
    {
        if (!auth()->check()) {
            return redirect()->to('/pwa/lotofacil/login');
        }

        $this->loadApostas();
    }

    public function loadApostas()
    {
        $userId = auth()->id();
        $bolao = $this->getBolaoAtivoModel();

        if (!$bolao) return;

        $this->bolaoAtivo = $bolao->toArray();

        $pivotRecords = DB::table('lotofacil_bolao_user')
            ->join('users', 'lotofacil_bolao_user.user_id', '=', 'users.id')
            ->where('lotofacil_bolao_id', $bolao->id)
            ->select('lotofacil_bolao_user.*', 'users.name as user_name')
            ->orderBy('lotofacil_bolao_user.id')
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
    }

    public function render()
    {
        return view('livewire.pwa.lotofacil.apostas')
            ->layout('components.layouts.pwa', [
                'title' => 'Apostas do Bolão Lotofácil',
                'showNav' => true,
                'showHeader' => true
            ]);
    }
}
