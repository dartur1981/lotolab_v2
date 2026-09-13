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
        $this->apiUrl = rtrim(config('services.python_api.url', 'http://127.0.0.1:5000'), '/');
    }

    /**
     * Extrai mensagem detalhada de erro da resposta HTTP da API Python.
     */
    protected function extractErrorMessage(Response $response, string $defaultMsg): string
    {
        $status = $response->status();
        $detail = $response->json('detail') ?? $response->json('message');

        if (!empty($detail)) {
            $msg = is_array($detail) ? json_encode($detail, JSON_UNESCAPED_UNICODE) : (string) $detail;
            return "{$msg} (HTTP {$status})";
        }

        $body = trim($response->body());
        if (!empty($body)) {
            $cleanBody = strip_tags($body);
            return "{$defaultMsg} (HTTP {$status}: " . substr($cleanBody, 0, 300) . ")";
        }

        return "{$defaultMsg} (HTTP {$status})";
    }

    /**
     * Lança uma lista de jogos no dispositivo.
     */
    public function lancarJogos(array $jogos): bool
    {
        try {
            $response = Http::timeout(600)->post("{$this->apiUrl}/bot/lancar-jogos", [
                'jogos' => $jogos,
            ]);

            if ($response->failed() || $response->json('status') !== 'success') {
                $errorMsg = $this->extractErrorMessage($response, 'Erro ao lançar jogos na API Python.');
                Log::error("LotofacilBot Erro (lancarJogos): {$errorMsg}");
                throw new \Exception($errorMsg);
            }

            return $response->json('data.sucesso') ?? false;
        } catch (ConnectionException $e) {
            $msg = "Não foi possível conectar ao serviço Python em {$this->apiUrl}. Verifique se o container 'lotolab-python-v2' está em execução.";
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
    public function irParaCarrinho(): bool
    {
        try {
            $response = Http::timeout(60)->post("{$this->apiUrl}/bot/ir-para-carrinho");

            if ($response->failed() || $response->json('status') !== 'success') {
                $errorMsg = $this->extractErrorMessage($response, 'Erro ao navegar para o carrinho na API Python.');
                Log::error("LotofacilBot Erro (irParaCarrinho): {$errorMsg}");
                throw new \Exception($errorMsg);
            }

            return $response->json('data.sucesso') ?? false;
        } catch (ConnectionException $e) {
            $msg = "Não foi possível conectar ao serviço Python em {$this->apiUrl}. Verifique se o container 'lotolab-python-v2' está em execução.";
            Log::error("LotofacilBot Erro de Conexão (irParaCarrinho): " . $e->getMessage());
            throw new \Exception($msg, 0, $e);
        } catch (\Exception $e) {
            Log::error("Erro no LotofacilBotService (irParaCarrinho): " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Lê os jogos atualmente no carrinho.
     * Retorna array de jogos (cada jogo é um array de 15 números).
     */
    public function lerCarrinho(): array
    {
        try {
            $response = Http::timeout(120)->post("{$this->apiUrl}/bot/ler-carrinho");

            if ($response->failed() || $response->json('status') !== 'success') {
                $errorMsg = $this->extractErrorMessage($response, 'Erro ao ler carrinho na API Python.');
                Log::error("LotofacilBot Erro (lerCarrinho): {$errorMsg}");
                throw new \Exception($errorMsg);
            }

            return $response->json('data.jogos_lidos') ?? [];
        } catch (ConnectionException $e) {
            $msg = "Não foi possível conectar ao serviço Python em {$this->apiUrl}. Verifique se o container 'lotolab-python-v2' está em execução.";
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
        try {
            $response = Http::timeout(120)->post("{$this->apiUrl}/bot/conciliar", [
                'jogos' => $jogosOriginais,
                'lidos' => $jogosLidos,
            ]);

            if ($response->failed() || $response->json('status') !== 'success') {
                $errorMsg = $this->extractErrorMessage($response, 'Erro ao conciliar jogos na API Python.');
                Log::error("LotofacilBot Erro (conciliar): {$errorMsg}");
                throw new \Exception($errorMsg);
            }

            return $response->json('data') ?? [];
        } catch (ConnectionException $e) {
            $msg = "Não foi possível conectar ao serviço Python em {$this->apiUrl}. Verifique se o container 'lotolab-python-v2' está em execução.";
            Log::error("LotofacilBot Erro de Conexão (conciliar): " . $e->getMessage());
            throw new \Exception($msg, 0, $e);
        } catch (\Exception $e) {
            Log::error("Erro no LotofacilBotService (conciliar): " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Executa o lançamento e em seguida a conciliação no carrinho num único processo.
     */
    public function lancarEConciliar(array $jogos, ?callable $callback = null, array $ids = [], string $modelType = 'lotofacil'): array
    {
        try {
            $response = Http::timeout(1200)->post("{$this->apiUrl}/bot/lancar-e-conciliar", [
                'jogos' => $jogos,
                'ids' => array_values(array_map('intval', $ids)),
                'model_type' => $modelType,
            ]);

            if ($response->failed() || $response->json('status') !== 'success') {
                $errorMsg = $this->extractErrorMessage($response, 'Erro na execução do robô na API Python.');
                Log::error("LotofacilBot Erro (lancarEConciliar): {$errorMsg}");
                throw new \Exception($errorMsg);
            }

            return $response->json('data') ?? [];
        } catch (ConnectionException $e) {
            $msg = "Não foi possível conectar ao serviço Python em {$this->apiUrl}. Verifique se o container 'lotolab-python-v2' está em execução.";
            Log::error("LotofacilBot Erro de Conexão (lancarEConciliar): " . $e->getMessage());
            throw new \Exception($msg, 0, $e);
        } catch (\Exception $e) {
            Log::error("Erro no LotofacilBotService (lancarEConciliar): " . $e->getMessage());
            throw $e;
        }
    }
}
