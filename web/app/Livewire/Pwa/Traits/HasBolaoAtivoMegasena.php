<?php

namespace App\Livewire\Pwa\Traits;

use App\Models\Megasena\Bolao;
use Illuminate\Support\Facades\Session;

trait HasBolaoAtivoMegasena
{
    public function getBolaoAtivoId()
    {
        $userId = auth()->id();

        // Obtém todos os bolões ativos do usuário na Mega-Sena
        $boloesAtivos = Bolao::whereHas('users', function($q) use ($userId) {
            $q->where('users.id', $userId);
        })->whereIn('status', [0, 1, 2])->latest()->get();

        if ($boloesAtivos->isEmpty()) {
            return null;
        }

        // Verifica se tem algum selecionado na sessão
        $sessaoBolaoId = Session::get('pwa_megasena_bolao_ativo_id');

        if ($sessaoBolaoId) {
            $valid = $boloesAtivos->firstWhere('id', $sessaoBolaoId);
            if ($valid) {
                return $sessaoBolaoId;
            }
        }

        // Padrão: primeiro da lista
        $novoId = $boloesAtivos->first()->id;
        Session::put('pwa_megasena_bolao_ativo_id', $novoId);

        return $novoId;
    }

    public function getBolaoAtivoModel(): ?Bolao
    {
        $id = $this->getBolaoAtivoId();
        if ($id) {
            return Bolao::find($id);
        }
        return null;
    }
}
