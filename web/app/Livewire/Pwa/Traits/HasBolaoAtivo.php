<?php

namespace App\Livewire\Pwa\Traits;

use App\Models\Lotofacil\Bolao;
use Illuminate\Support\Facades\Session;

trait HasBolaoAtivo
{
    public function getBolaoAtivoId()
    {
        $userId = auth()->id();

        // Obtém todos os bolões ativos do usuário
        $boloesAtivos = Bolao::whereHas('users', function($q) use ($userId) {
            $q->where('users.id', $userId);
        })->where('status', 1)->latest()->get();

        if ($boloesAtivos->isEmpty()) {
            return null;
        }

        // Verifica se tem algum selecionado na sessão
        $sessaoBolaoId = Session::get('pwa_bolao_ativo_id');

        if ($sessaoBolaoId) {
            // Verifica se o bolão da sessão ainda é válido e ativo
            $valid = $boloesAtivos->firstWhere('id', $sessaoBolaoId);
            if ($valid) {
                return $sessaoBolaoId;
            }
        }

        // Se não tiver na sessão ou não for válido, pega o primeiro da lista e joga na sessão
        $novoId = $boloesAtivos->first()->id;
        Session::put('pwa_bolao_ativo_id', $novoId);

        return $novoId;
    }

    public function getBolaoAtivoModel()
    {
        $id = $this->getBolaoAtivoId();
        if ($id) {
            return Bolao::find($id);
        }
        return null;
    }
}
