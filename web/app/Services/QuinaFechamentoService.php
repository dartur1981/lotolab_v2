<?php

namespace App\Services;

use App\Models\Quina\Bolao;
use App\Models\Quina\QuinaFechamento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class QuinaFechamentoService
{
    /**
     * Gera os fechamentos para o bolão da Quina com base nas dezenas selecionadas pelos participantes.
     */
    public function gerarFechamento(int $bolaoId, int $quantidadeJogos = 10): ?QuinaFechamento
    {
        $bolao = Bolao::findOrFail($bolaoId);

        $pivotRecords = DB::table('quina_bolao_user')
            ->where('quina_bolao_id', $bolaoId)
            ->get();

        $dezenasPool = [];
        foreach ($pivotRecords as $record) {
            $selecionadas = json_decode($record->numeros_selecionados, true) ?? [];
            $dezenasPool = array_merge($dezenasPool, $selecionadas);
        }

        $dezenasUnicas = array_values(array_unique($dezenasPool));
        sort($dezenasUnicas);
        $totalPool = count($dezenasUnicas);

        if ($totalPool < 5) {
            throw new \Exception("Não é possível gerar fechamento. O pool possui apenas {$totalPool} dezenas (mínimo 5).");
        }

        $fechamento = QuinaFechamento::create([
            'bolao_id' => $bolaoId,
            'quantidade_jogos' => $quantidadeJogos,
            'dezenas' => $dezenasUnicas,
        ]);

        try {
            $pythonUrl = rtrim(config('services.python_api.url', 'http://127.0.0.1:5000'), '/');
            $response = Http::timeout(120)->post("{$pythonUrl}/quina/gerar-fechamento", [
                'bolao_id' => $bolaoId,
                'fechamento_id' => $fechamento->id,
                'quantidade_jogos' => $quantidadeJogos,
                'dezenas' => $dezenasUnicas,
            ]);

            if ($response->failed() || $response->json('status') !== 'success') {
                $errorMsg = $response->json('detail') ?? $response->json('message') ?? 'Erro de comunicação com a API Python da Quina.';
                throw new \Exception($errorMsg);
            }

            // Atualiza os valores de rateio no cadastro do bolão
            $precoAposta = (float) (\App\Models\ConfiguracaoApp::where('chave', 'quina_valor_aposta')->value('valor') ?? 2.50);
            $totalJogosGerados = $fechamento->jogos()->count() ?: $quantidadeJogos;
            $valorTotal = $totalJogosGerados * $precoAposta;

            $totalParticipantes = DB::table('quina_bolao_user')
                ->where('quina_bolao_id', $bolaoId)
                ->count();

            $valorCota = $totalParticipantes > 0 ? round($valorTotal / $totalParticipantes, 2) : $valorTotal;

            $bolao->update([
                'valor_total' => $valorTotal,
                'valor_cota' => $valorCota,
            ]);

            return $fechamento->refresh();
        } catch (\Exception $e) {
            $fechamento->delete();
            throw $e;
        }
    }

    public function regerarFechamento(QuinaFechamento $fechamento, int $quantidadeJogos = 10): QuinaFechamento
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
