<?php

namespace App\Livewire\Pwa\Megasena;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Megasena\Bolao;

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
            
            $userId = $this->selectedUserId;
            $boloesAtivos = Bolao::whereHas('users', function($q) use ($userId) {
                $q->where('users.id', $userId);
            })->whereIn('status', [0, 1, 2])->get();
            
            $pendingBoloes = $boloesAtivos->filter(function($bolao) use ($userId) {
                return $bolao->isPendingForUser($userId);
            });
            
            $pendingBoloesCount = $pendingBoloes->count();
            
            if ($pendingBoloesCount > 1) {
                return redirect('/pwa/megasena/boloes');
            } else {
                if ($pendingBoloesCount == 1) {
                    session()->put('pwa_megasena_bolao_ativo_id', $pendingBoloes->first()->id);
                }
                return redirect()->intended('/pwa/megasena/dashboard');
            }
        }
    }

    public function render()
    {
        $participantes = [];
        
        if (!$this->selectedUserId) {
            $participantesBrutos = User::with(['megasenaBoloes' => function($q) {
                $q->whereIn('status', [0, 1]);
            }])->whereHas('megasenaBoloes', function($query) {
                $query->whereIn('status', [0, 1]);
            })->orderBy('name')->get();

            foreach ($participantesBrutos as $user) {
                $isPending = false;
                foreach ($user->megasenaBoloes as $bolao) {
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

        return view('livewire.pwa.megasena.login', [
            'participantes' => $participantes,
            'selectedUser' => $selectedUser
        ])->layout('components.layouts.pwa', [
            'title' => 'Entrar - Mega-Sena Bolão',
            'showHeader' => false,
            'showNav' => false
        ]);
    }
}
