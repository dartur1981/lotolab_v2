<?php

namespace App\Services;

use App\Models\Megasena\Bolao;
use App\Models\Megasena\MegasenaFechamento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class MegasenaFechamentoService
{
    /**
     * Gera os fechamentos para o bolão da Mega-Sena com base nas dezenas selecionadas pelos participantes.
     */
    public function gerarFechamento(int $bolaoId, int $quantidadeJogos = 10): ?MegasenaFechamento
    {
        $bolao = Bolao::findOrFail($bolaoId);

        $pivotRecords = DB::table('megasena_bolao_user')
            ->where('megasena_bolao_id', $bolaoId)
            ->get();

        $dezenasPool = [];
        foreach ($pivotRecords as $record) {
            $selecionadas = json_decode($record->numeros_selecionados, true) ?? [];
            $dezenasPool = array_merge($dezenasPool, $selecionadas);
        }

        $dezenasUnicas = array_values(array_unique($dezenasPool));
        sort($dezenasUnicas);
        $totalPool = count($dezenasUnicas);

        if ($totalPool < 6) {
            throw new \Exception("Não é possível gerar fechamento. O pool possui apenas {$totalPool} dezenas (mínimo 6).");
        }

        $fechamento = MegasenaFechamento::create([
            'bolao_id' => $bolaoId,
            'quantidade_jogos' => $quantidadeJogos,
            'dezenas' => $dezenasUnicas,
        ]);

        try {
            $pythonUrl = rtrim(config('services.python_api.url', 'http://127.0.0.1:5000'), '/');
            $response = Http::timeout(120)->post("{$pythonUrl}/megasena/gerar-fechamento", [
                'bolao_id' => $bolaoId,
                'fechamento_id' => $fechamento->id,
                'quantidade_jogos' => $quantidadeJogos,
                'dezenas' => $dezenasUnicas,
            ]);

            if ($response->failed() || $response->json('status') !== 'success') {
                $errorMsg = $response->json('detail') ?? $response->json('message') ?? 'Erro de comunicação com a API Python da Mega-Sena.';
                throw new \Exception($errorMsg);
            }

            // Atualiza os valores de rateio no cadastro do bolão
            $precoAposta = (float) (\App\Models\ConfiguracaoApp::where('chave', 'megasena_valor_aposta')->value('valor') ?? 5.00);
            $totalJogosGerados = $fechamento->jogos()->count() ?: $quantidadeJogos;
            $valorTotal = $totalJogosGerados * $precoAposta;

            $totalParticipantes = DB::table('megasena_bolao_user')
                ->where('megasena_bolao_id', $bolaoId)
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

    public function regerarFechamento(MegasenaFechamento $fechamento, int $quantidadeJogos = 10): MegasenaFechamento
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
