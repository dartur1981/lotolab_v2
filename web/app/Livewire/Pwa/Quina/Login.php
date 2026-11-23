<?php

namespace App\Livewire\Pwa\Quina;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Quina\Bolao;

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
            $boloesAtivos = Bolao::whereHas('users', function($q) use ($userId) {
                $q->where('users.id', $userId);
            })->whereIn('status', [0, 1, 2])->get();
            
            $pendingBoloes = $boloesAtivos->filter(function($bolao) use ($userId) {
                return $bolao->isPendingForUser($userId);
            });
            
            $pendingBoloesCount = $pendingBoloes->count();
            
            if ($pendingBoloesCount > 1) {
                return redirect('/pwa/quina/boloes');
            } else {
                if ($pendingBoloesCount == 1) {
                    session()->put('pwa_quina_bolao_ativo_id', $pendingBoloes->first()->id);
                }
                return redirect('/pwa/quina/dashboard');
            }
        }
    }

    public function render()
    {
        $participantes = [];
        
        if (!$this->selectedUserId) {
            $participantesBrutos = User::with(['quinaBoloes' => function($q) {
                $q->whereIn('status', [0, 1]);
            }])->whereHas('quinaBoloes', function($query) {
                $query->whereIn('status', [0, 1]);
            })->orderBy('name')->get();

            foreach ($participantesBrutos as $user) {
                $isPending = false;
                foreach ($user->quinaBoloes as $bolao) {
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

        $selectedUser = $this->selectedUserId ? User::find($this->selectedUserId) : null;
        $totalBoloesAbertos = Bolao::whereIn('status', [0, 1])->count();

        return view('livewire.pwa.quina.login', [
            'participantes' => $participantes,
            'selectedUser' => $selectedUser,
            'totalBoloesAbertos' => $totalBoloesAbertos
        ])->layout('components.layouts.pwa', [
            'title' => 'Entrar - Quina Bolão',
            'showHeader' => false,
            'showNav' => false,
            'loteria' => 'quina'
        ]);
    }
}
