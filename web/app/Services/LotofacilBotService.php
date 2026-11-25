<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LotofacilBotService
{
    protected string $apiUrl;

    public function __construct()
    {
        $this->apiUrl = rtrim(config('services.python_api.url', 'http://lotolab-python-v2:5000'), '/');
    }

    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    /**
     * Extrai mensagem detalhada de erro da resposta HTTP da API Python.
     */
    protected function extractErrorMessage(Response $response, string $defaultMsg, string $url = ''): string
    {
        $status = $response->status();
        $detail = $response->json('detail') ?? $response->json('message');
        $target = $url ? " [URL: {$url}]" : "";

        if (!empty($detail)) {
            $msg = is_array($detail) ? json_encode($detail, JSON_UNESCAPED_UNICODE) : (string) $detail;
            return "{$msg}{$target} (HTTP {$status})";
        }

        $body = trim($response->body());
        if (!empty($body)) {
            $cleanBody = strip_tags($body);
            return "{$defaultMsg}{$target} (HTTP {$status}: " . substr($cleanBody, 0, 300) . ")";
        }

        return "{$defaultMsg}{$target} (HTTP {$status})";
    }

    /**
     * Lança uma lista de jogos no dispositivo (Etapa 1 - sem ir para o carrinho/conciliar).
     */
    public function lancarJogos(array $jogos, array $ids = [], string $modelType = 'lotofacil'): array
    {
        $url = "{$this->apiUrl}/bot/lancar-jogos";
        try {
            $response = Http::timeout(1200)->post($url, [
                'jogos' => $jogos,
                'ids' => array_values(array_map('intval', $ids)),
                'model_type' => $modelType,
            ]);

            if ($response->failed() || $response->json('status') !== 'success') {
                $errorMsg = $this->extractErrorMessage($response, 'Erro ao lançar jogos na API Python.', $url);
                Log::error("LotofacilBot Erro (lancarJogos): {$errorMsg}");
                throw new \Exception($errorMsg);
            }

            return $response->json('data') ?? [];
        } catch (ConnectionException $e) {
            $msg = "Não foi possível conectar ao serviço Python em {$url}. Verifique se o container 'lotolab-python-v2' está em execução.";
            Log::error("LotofacilBot Erro de Conexão (lancarJogos): " . $e->getMessage());
            throw new \Exception($msg, 0, $e);
        } catch (\Exception $e) {
            Log::error("Erro no LotofacilBotService (lancarJogos): " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Navega para a tela do carrinho de compras.
     */
    public function irParaCarrinho(string $modelType = 'lotofacil'): bool
    {
        $url = "{$this->apiUrl}/bot/ir-para-carrinho?model_type={$modelType}";
        try {
            $response = Http::timeout(60)->post($url);

            if ($response->failed() || $response->json('status') !== 'success') {
                $errorMsg = $this->extractErrorMessage($response, 'Erro ao navegar para o carrinho na API Python.', $url);
                Log::error("LotofacilBot Erro (irParaCarrinho): {$errorMsg}");
                throw new \Exception($errorMsg);
            }

            return $response->json('data.sucesso') ?? false;
        } catch (ConnectionException $e) {
            $msg = "Não foi possível conectar ao serviço Python em {$url}. Verifique se o container 'lotolab-python-v2' está em execução.";
            Log::error("LotofacilBot Erro de Conexão (irParaCarrinho): " . $e->getMessage());
            throw new \Exception($msg, 0, $e);
        } catch (\Exception $e) {
            Log::error("Erro no LotofacilBotService (irParaCarrinho): " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Navega para o carrinho (se ainda não estiver nele) e lê todos os jogos do carrinho.
     */
    public function irELerCarrinho(string $modelType = 'lotofacil'): array
    {
        $url = "{$this->apiUrl}/bot/ir-e-ler-carrinho?model_type={$modelType}";
        try {
            $response = Http::timeout(600)->post($url);

            if ($response->failed() || $response->json('status') !== 'success') {
                $errorMsg = $this->extractErrorMessage($response, 'Erro ao abrir e ler carrinho na API Python.', $url);
                Log::error("LotofacilBot Erro (irELerCarrinho): {$errorMsg}");
                throw new \Exception($errorMsg);
            }

            return $response->json('data') ?? [];
        } catch (ConnectionException $e) {
            $msg = "Não foi possível conectar ao serviço Python em {$url}. Verifique se o container 'lotolab-python-v2' está em execução.";
            Log::error("LotofacilBot Erro de Conexão (irELerCarrinho): " . $e->getMessage());
            throw new \Exception($msg, 0, $e);
        } catch (\Exception $e) {
            Log::error("Erro no LotofacilBotService (irELerCarrinho): " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Lê os jogos atualmente no carrinho.
     * Retorna array de jogos (cada jogo é um array de números).
     */
    public function lerCarrinho(string $modelType = 'lotofacil'): array
    {
        $url = "{$this->apiUrl}/bot/ler-carrinho?model_type={$modelType}";
        try {
            $response = Http::timeout(120)->post($url);

            if ($response->failed() || $response->json('status') !== 'success') {
                $errorMsg = $this->extractErrorMessage($response, 'Erro ao ler carrinho na API Python.', $url);
                Log::error("LotofacilBot Erro (lerCarrinho): {$errorMsg}");
                throw new \Exception($errorMsg);
            }

            return $response->json('data.jogos_lidos') ?? [];
        } catch (ConnectionException $e) {
            $msg = "Não foi possível conectar ao serviço Python em {$url}. Verifique se o container 'lotolab-python-v2' está em execução.";
            Log::error("LotofacilBot Erro de Conexão (lerCarrinho): " . $e->getMessage());
            throw new \Exception($msg, 0, $e);
        } catch (\Exception $e) {
            Log::error("Erro no LotofacilBotService (lerCarrinho): " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Concilia a lista original com a lista lida.
     */
    public function conciliar(array $jogosOriginais, array $jogosLidos): array
    {
        $url = "{$this->apiUrl}/bot/conciliar";
        try {
            $response = Http::timeout(120)->post($url, [
                'jogos' => $jogosOriginais,
                'lidos' => $jogosLidos,
            ]);

            if ($response->failed() || $response->json('status') !== 'success') {
                $errorMsg = $this->extractErrorMessage($response, 'Erro ao conciliar jogos na API Python.', $url);
                Log::error("LotofacilBot Erro (conciliar): {$errorMsg}");
                throw new \Exception($errorMsg);
            }

            return $response->json('data') ?? [];
        } catch (ConnectionException $e) {
            $msg = "Não foi possível conectar ao serviço Python em {$url}. Verifique se o container 'lotolab-python-v2' está em execução.";
            Log::error("LotofacilBot Erro de Conexão (conciliar): " . $e->getMessage());
            throw new \Exception($msg, 0, $e);
        } catch (\Exception $e) {
            Log::error("Erro no LotofacilBotService (conciliar): " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Normaliza o campo dezenas (JSON, CSV ou array) para um array ordenado de inteiros.
     */
    public static function parseDezenas($val): array
    {
        if (is_string($val)) {
            $decoded = json_decode($val, true);
            if (is_array($decoded)) {
                $dezenas = array_map('intval', $decoded);
            } else {
                $parts = array_filter(array_map('trim', explode(',', $val)), fn ($v) => $v !== '');
                $dezenas = array_map('intval', $parts);
            }
        } elseif (is_array($val)) {
            $dezenas = array_map('intval', $val);
        } else {
            $dezenas = [];
        }

        sort($dezenas);
        return array_values($dezenas);
    }

    /**
     * Concilia os jogos de um fechamento com a lista de jogos lidos no carrinho.
     * Promove jogos Lançados (3) ou Pendentes/Divergentes (0/1) encontrados no carrinho para Apostado (4),
     * marca jogos Lançados (3) ausentes como Divergente (1) e identifica extras reais.
     */
    public function conciliarFechamentoComCarrinho($fechamento, array $jogosLidos): array
    {
        $restantesCarrinho = [];
        foreach ($jogosLidos as $jogo) {
            $norm = self::parseDezenas($jogo);
            if (!empty($norm)) {
                $restantesCarrinho[] = $norm;
            }
        }

        $consumirDoCarrinho = function (array $dezenasAlvo) use (&$restantesCarrinho): bool {
            foreach ($restantesCarrinho as $idx => $jogoCarrinho) {
                if ($jogoCarrinho === $dezenasAlvo) {
                    unset($restantesCarrinho[$idx]);
                    $restantesCarrinho = array_values($restantesCarrinho);
                    return true;
                }
            }
            return false;
        };

        $todosJogos = $fechamento->jogos()->get();

        $jaApostados = 0;
        $conciliadosDeLancado = 0;
        $conciliadosDePendente = 0;
        $divergentes = 0;

        // 1. Primeiro desconta os que já estão Apostados (4)
        foreach ($todosJogos as $record) {
            if ((string) $record->status !== '4') {
                continue;
            }
            $dezenas = self::parseDezenas($record->dezenas);
            if (!empty($dezenas) && $consumirDoCarrinho($dezenas)) {
                $jaApostados++;
            }
        }

        // 2. Concilia os que ficaram como Lançado (3) — ex: queda no app da Caixa antes da Fase 2
        foreach ($todosJogos as $record) {
            if ((string) $record->status !== '3') {
                continue;
            }
            $dezenas = self::parseDezenas($record->dezenas);
            if (empty($dezenas)) {
                continue;
            }
            if ($consumirDoCarrinho($dezenas)) {
                $record->update(['status' => '4']);
                $conciliadosDeLancado++;
            } else {
                $record->update(['status' => '1']);
                $divergentes++;
            }
        }

        // 3. Confere se algum Pendente (0) ou Divergente (1) já está no carrinho
        foreach ($todosJogos as $record) {
            if (!in_array((string) $record->status, ['0', '1'], true)) {
                continue;
            }
            $dezenas = self::parseDezenas($record->dezenas);
            if (!empty($dezenas) && $consumirDoCarrinho($dezenas)) {
                $record->update(['status' => '4']);
                $conciliadosDePendente++;
            }
        }

        return [
            'total_lidos' => count($jogosLidos),
            'ja_apostados' => $jaApostados,
            'conciliados_agora' => $conciliadosDeLancado + $conciliadosDePendente,
            'conciliados_de_lancado' => $conciliadosDeLancado,
            'conciliados_de_pendente' => $conciliadosDePendente,
            'divergentes' => $divergentes,
            'extras' => count($restantesCarrinho),
            'jogos_extras' => $restantesCarrinho,
        ];
    }

    /**
     * Executa o lançamento e em seguida a conciliação no carrinho num único processo.
     */
    public function lancarEConciliar(array $jogos, ?callable $callback = null, array $ids = [], string $modelType = 'lotofacil', array $jogosAnteriores = []): array
    {
        $url = "{$this->apiUrl}/bot/lancar-e-conciliar";
        try {
            $response = Http::timeout(1200)->post($url, [
                'jogos' => $jogos,
                'ids' => array_values(array_map('intval', $ids)),
                'model_type' => $modelType,
                'jogos_esperados_carrinho' => array_values($jogosAnteriores),
            ]);

            if ($response->failed() || $response->json('status') !== 'success') {
                $errorMsg = $this->extractErrorMessage($response, 'Erro na execução do robô na API Python.', $url);
                Log::error("LotofacilBot Erro (lancarEConciliar): {$errorMsg}");
                throw new \Exception($errorMsg);
            }

            return $response->json('data') ?? [];
        } catch (ConnectionException $e) {
            $msg = "Não foi possível conectar ao serviço Python em {$url}. Verifique se o container 'lotolab-python-v2' está em execução.";
            Log::error("LotofacilBot Erro de Conexão (lancarEConciliar): " . $e->getMessage());
            throw new \Exception($msg, 0, $e);
        } catch (\Exception $e) {
            Log::error("Erro no LotofacilBotService (lancarEConciliar): " . $e->getMessage());
            throw $e;
        }
    }
}
