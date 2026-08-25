<?php

namespace App\Livewire\Pwa\Lotofacil;

use Livewire\Component;
use App\Models\Lotofacil\Bolao;
use Illuminate\Support\Facades\Session;

class Boloes extends Component
{
    public $boloes = [];

    public function mount()
    {
        if (!auth()->check()) {
            return redirect('/pwa/lotofacil/login');
        }

        $userId = auth()->id();
        $this->boloes = Bolao::whereHas('users', function($q) use ($userId) {
            $q->where('users.id', $userId);
        })->where('status', 1)->latest()->get()->filter(function($bolao) use ($userId) {
            return $bolao->isPendingForUser($userId);
        })->toArray();
        
        $this->boloes = array_values($this->boloes); // Reset array keys

        if (count($this->boloes) == 1) {
            return $this->selecionarBolao($this->boloes[0]['id']);
        } elseif (count($this->boloes) == 0) {
            $hasAnyBolao = Bolao::whereHas('users', function($q) use ($userId) {
                $q->where('users.id', $userId);
            })->where('status', 1)->exists();
            
            if ($hasAnyBolao) {
                return redirect('/pwa/lotofacil/dashboard');
            }
        }
    }

    public function selecionarBolao($id)
    {
        Session::put('pwa_bolao_ativo_id', $id);
        return redirect('/pwa/lotofacil/dashboard');
    }

    public function render()
    {
        return view('livewire.pwa.lotofacil.boloes')
            ->layout('components.layouts.pwa', [
                'title' => 'Selecionar Bolão - LotoLab Lotofácil',
                'showHeader' => false,
                'showNav' => false
            ]);
    }
}
