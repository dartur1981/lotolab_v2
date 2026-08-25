<?php

namespace App\Services;

use App\Models\Lotofacil\Bolao;
use App\Models\Lotofacil\LotofacilFechamento;
use Illuminate\Support\Facades\DB;

class LotofacilFechamentoService
{
    /**
     * Gera os fechamentos para o bolão com base nas dezenas selecionadas pelos participantes.
     */
    public function gerarFechamento(int $bolaoId, int $quantidadeJogos = 15): ?LotofacilFechamento
    {
        $bolao = Bolao::findOrFail($bolaoId);

        // 1. Coleta todas as dezenas dos participantes
        $pivotRecords = DB::table('lotofacil_bolao_user')
            ->where('lotofacil_bolao_id', $bolaoId)
            ->get();

        $dezenasPool = [];
        foreach ($pivotRecords as $record) {
            $selecionadas = json_decode($record->numeros_selecionados, true) ?? [];
            $dezenasPool = array_merge($dezenasPool, $selecionadas);
        }

        $dezenasUnicas = array_values(array_unique($dezenasPool));
        sort($dezenasUnicas);
        $totalPool = count($dezenasUnicas);

        if ($totalPool < 15) {
            throw new \Exception("Não é possível gerar fechamento. O pool possui apenas {$totalPool} dezenas (mínimo 15).");
        }

        $fechamento = LotofacilFechamento::create([
            'bolao_id' => $bolaoId,
            'quantidade_jogos' => $quantidadeJogos,
            'dezenas' => $dezenasUnicas,
            'grupos' => null, // Será preenchido pelo Python
        ]);

        try {
            // Envia a requisição para a API Python na porta 5000 (onde a lógica bruta agora reside)
            $response = \Illuminate\Support\Facades\Http::timeout(120)->post('http://127.0.0.1:5000/gerar-fechamento', [
                'bolao_id' => $bolaoId,
                'fechamento_id' => $fechamento->id,
                'quantidade_jogos' => $quantidadeJogos,
                'dezenas' => $dezenasUnicas,
            ]);

            if ($response->failed() || $response->json('status') !== 'ok') {
                $errorMsg = $response->json('detail') ?? $response->json('message') ?? 'Erro de comunicação com a API Python matemática.';
                throw new \Exception($errorMsg);
            }

            return $fechamento->refresh();
        } catch (\Exception $e) {
            $fechamento->delete();
            throw $e;
        }
    }

    /**
     * Apaga os jogos atuais e gera novos.
     */
    public function regerarFechamento(LotofacilFechamento $fechamento, int $quantidadeJogos = 15): LotofacilFechamento
    {
        DB::beginTransaction();
        try {
            $fechamento->jogos()->delete();
            $fechamento->delete(); 
            DB::commit();
            return $this->gerarFechamento($fechamento->bolao_id, $quantidadeJogos);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
