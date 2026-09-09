<?php

namespace App\Services;

use App\Models\Lotofacil\EstrategiaFechamento;
use Illuminate\Support\Facades\Http;

class EstrategiaFechamentoService
{
    /**
     * Cria o registro e chama a API Python para gerar o fechamento avulso (Estrategia).
     */
    public function gerarFechamento(array $dezenas, int $quantidadeJogos = 15): ?EstrategiaFechamento
    {
        $fechamento = EstrategiaFechamento::create([
            'quantidade_jogos' => $quantidadeJogos,
            'dezenas' => $dezenas,
            'grupos' => null, // Sera preenchido pelo Python
        ]);

        try {
            // Envia a requisicao para a API Python na porta 5000
            $pythonUrl = rtrim(config('services.python_api.url', 'http://127.0.0.1:5000'), '/');
            $response = Http::timeout(120)->post("{$pythonUrl}/gerar-fechamento-estrategia", [
                'estrategia_fechamento_id' => $fechamento->id,
                'quantidade_jogos' => $quantidadeJogos,
                'dezenas' => $dezenas,
            ]);

            if ($response->failed() || $response->json('status') !== 'ok') {
                $errorMsg = $response->json('detail') ?? $response->json('message') ?? 'Erro de comunicacao com a API Python matematica.';
                throw new \Exception($errorMsg);
            }

            return $fechamento->refresh();
        } catch (\Exception $e) {
            $fechamento->delete();
            throw $e;
        }
    }
}
