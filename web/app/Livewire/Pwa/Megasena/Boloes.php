<?php

namespace App\Livewire\Pwa\Megasena;

use Livewire\Component;
use App\Models\Megasena\Bolao;
use Illuminate\Support\Facades\Session;

class Boloes extends Component
{
    public $boloes = [];

    public function mount()
    {
        if (!auth()->check()) {
            return redirect('/pwa/megasena/login');
        }

        $userId = auth()->id();
        $this->boloes = Bolao::whereHas('users', function($q) use ($userId) {
            $q->where('users.id', $userId);
        })->whereIn('status', [0, 1, 2])->latest()->get()->filter(function($bolao) use ($userId) {
            return $bolao->isPendingForUser($userId);
        })->toArray();
        
        $this->boloes = array_values($this->boloes);

        if (count($this->boloes) == 1) {
            return $this->selecionarBolao($this->boloes[0]['id']);
        } elseif (count($this->boloes) == 0) {
            $hasAnyBolao = Bolao::whereHas('users', function($q) use ($userId) {
                $q->where('users.id', $userId);
            })->whereIn('status', [0, 1, 2])->exists();
            
            if ($hasAnyBolao) {
                return redirect('/pwa/megasena/dashboard');
            }
        }
    }

    public function selecionarBolao($id)
    {
        Session::put('pwa_megasena_bolao_ativo_id', $id);
        return redirect('/pwa/megasena/dashboard');
    }

    public function render()
    {
        return view('livewire.pwa.megasena.boloes')
            ->layout('components.layouts.pwa', [
                'title' => 'Meus Bolões - Mega-Sena',
                'showHeader' => false,
                'showNav' => false
            ]);
    }
}
