<?php

namespace App\Livewire\Pwa\Lotofacil;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Login extends Component
{
    public ?int $selectedUserId = null;

    public function selectUser($userId)
    {
        $this->selectedUserId = $userId;
    }

    public function cancelSelection()
    {
        $this->selectedUserId = null;
    }

    public function login()
    {
        if ($this->selectedUserId) {
            Auth::loginUsingId($this->selectedUserId, true);
            session()->regenerate();
            session()->forget('url.intended');
            
            $userId = $this->selectedUserId;
            $boloesAtivos = \App\Models\Lotofacil\Bolao::whereHas('users', function($q) use ($userId) {
                $q->where('users.id', $userId);
            })->where('status', 1)->get();
            
            $pendingBoloes = $boloesAtivos->filter(function($bolao) use ($userId) {
                return $bolao->isPendingForUser($userId);
            });
            
            $pendingBoloesCount = $pendingBoloes->count();
            
            if ($pendingBoloesCount > 1) {
                return redirect('/pwa/lotofacil/boloes');
            } else {
                if ($pendingBoloesCount == 1) {
                    session()->put('pwa_bolao_ativo_id', $pendingBoloes->first()->id);
                }
                return redirect('/pwa/lotofacil/dashboard');
            }
        }
    }

    public function render()
    {
        $participantes = [];
        
        if (!$this->selectedUserId) {
            $participantesBrutos = \App\Models\User::with(['boloes_lotofacil' => function($q) {
                $q->whereIn('status', [0, 1]);
            }])->whereHas('boloes_lotofacil', function($query) {
                $query->whereIn('status', [0, 1]);
            })->orderBy('name')->get();

            $participantes = [];
            foreach ($participantesBrutos as $user) {
                $isPending = false;
                
                foreach ($user->boloes_lotofacil as $bolao) {
                    if ($bolao->isPendingForUser($user->id)) {
                        $isPending = true;
                        break;
                    }
                }
                
                if ($isPending) {
                    $participantes[] = $user;
                }
            }
        }

        $selectedUser = $this->selectedUserId ? \App\Models\User::find($this->selectedUserId) : null;
        $totalBoloesAbertos = \App\Models\Lotofacil\Bolao::whereIn('status', [0, 1])->count();

        return view('livewire.pwa.lotofacil.login', compact('participantes', 'selectedUser', 'totalBoloesAbertos'))
            ->layout('components.layouts.pwa', [
                'title' => 'Fazer Login - LotoLab Lotofácil',
                'showHeader' => false
            ]);
    }
}
