<?php

namespace App\Livewire\Pwa\Lotofacil;

use Livewire\Component;
use App\Models\Lotofacil\Bolao;
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
        })->where('status', 1)->latest()->get()->toArray();

        $this->bolaoSelecionadoId = Session::get('pwa_bolao_ativo_id');

        // Se tiver mais de um e nenhum selecionado válido, pega o primeiro
        if (count($this->boloes) > 0 && !$this->bolaoSelecionadoId) {
            $this->bolaoSelecionadoId = $this->boloes[0]['id'];
            Session::put('pwa_bolao_ativo_id', $this->bolaoSelecionadoId);
        }
    }

    public function updatedBolaoSelecionadoId($value)
    {
        if ($value) {
            Session::put('pwa_bolao_ativo_id', $value);
            $this->redirect(request()->header('Referer') ?? '/pwa/lotofacil/dashboard');
        }
    }

    public function render()
    {
        return view('livewire.pwa.lotofacil.bolao-selector');
    }
}
