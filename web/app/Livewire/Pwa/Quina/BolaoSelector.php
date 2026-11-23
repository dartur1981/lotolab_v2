<?php

namespace App\Livewire\Pwa\Quina;

use Livewire\Component;
use App\Models\Quina\Bolao;
use Illuminate\Support\Facades\Session;

class BolaoSelector extends Component
{
    public $boloes = [];
    public $bolaoSelecionadoId;

    public function mount()
    {
        if (!auth()->check()) {
            return;
        }

        $userId = auth()->id();
        $this->boloes = Bolao::whereHas('users', function($q) use ($userId) {
            $q->where('users.id', $userId);
        })->whereIn('status', [0, 1, 2])->latest()->get()->toArray();

        $this->bolaoSelecionadoId = Session::get('pwa_quina_bolao_ativo_id');

        if (count($this->boloes) > 0 && !$this->bolaoSelecionadoId) {
            $this->bolaoSelecionadoId = $this->boloes[0]['id'];
            Session::put('pwa_quina_bolao_ativo_id', $this->bolaoSelecionadoId);
        }
    }

    public function updatedBolaoSelecionadoId($value)
    {
        if ($value) {
            Session::put('pwa_quina_bolao_ativo_id', $value);
            $this->redirect(request()->header('Referer') ?? '/pwa/quina/dashboard');
        }
    }

    public function render()
    {
        return view('livewire.pwa.quina.bolao-selector');
    }
}
