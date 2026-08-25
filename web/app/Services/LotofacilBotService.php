<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Log;

class LotofacilBotService
{
    protected string $pythonPath = 'python'; // Ou 'python3', dependendo do ambiente
    protected string $scriptPath;

    public function __construct()
    {
        $this->scriptPath = base_path('../python/bot_lotofacil.py');
        $venvPython = base_path('../python/.venv/Scripts/python.exe');
        if (file_exists($venvPython)) {
            $this->pythonPath = $venvPython;
        }
    }

    /**
     * Executa o comando Python e retorna o output parseado de JSON.
     */
    protected function runCommand(array $args, ?callable $callback = null): array
    {
        $command = array_merge([$this->pythonPath, $this->scriptPath], $args);
        
        $env = getenv();
        if (empty($env['SystemRoot']) && empty($env['SYSTEMROOT'])) {
            $env['SystemRoot'] = 'C:\\Windows';
            $env['SYSTEMROOT'] = 'C:\\Windows';
        }
        
        $process = new Process($command, null, $env);
        $process->setTimeout(900);
        
        $outputJson = '';
        
        try {
            $process->run(function ($type, $buffer) use ($callback, &$outputJson) {
                if ($type === Process::OUT) {
                    $outputJson .= $buffer;
                }
                
                if ($callback) {
                    $callback($type, $buffer);
                }
            });
            
            if (!$process->isSuccessful()) {
                throw new \Exception("Erro no script Python: " . $process->getErrorOutput());
            }
            
            // O python script só dá print(json) no final
            $result = json_decode($outputJson, true);
            
            if (!$result || $result['status'] === 'error') {
                $errorMsg = $result['message'] ?? 'Erro desconhecido no Python';
                Log::error("LotofacilBot Erro: {$errorMsg}");
                throw new \Exception($errorMsg);
            }
            
            return $result['data'] ?? [];
            
        } catch (\Exception $e) {
            Log::error("Erro no LotofacilBotService: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Lança uma lista de jogos no dispositivo.
     */
    public function lancarJogos(array $jogos): bool
    {
        $data = $this->runCommand([
            '--action', 'lancar',
            '--jogos', json_encode($jogos)
        ]);
        
        return $data['sucesso'] ?? false;
    }

    /**
     * Navega para a tela do carrinho de compras.
     */
    public function irParaCarrinho(): bool
    {
        $data = $this->runCommand([
            '--action', 'carrinho'
        ]);
        
        return $data['sucesso'] ?? false;
    }

    /**
     * Lê os jogos atualmente no carrinho.
     * Retorna array de jogos (cada jogo é um array de 15 números).
     */
    public function lerCarrinho(): array
    {
        $data = $this->runCommand([
            '--action', 'ler'
        ]);
        
        return $data['jogos_lidos'] ?? [];
    }

    /**
     * Concilia a lista original com a lista lida (faz no python, mas poderia ser aqui no PHP).
     */
    public function conciliar(array $jogosOriginais, array $jogosLidos): array
    {
        $data = $this->runCommand([
            '--action', 'conciliar',
            '--jogos', json_encode($jogosOriginais),
            '--lidos', json_encode($jogosLidos)
        ]);
        
        return $data;
    }

    /**
     * Executa o lançamento e em seguida a conciliação no carrinho num único processo.
     */
    public function lancarEConciliar(array $jogos, ?callable $callback = null): array
    {
        $data = $this->runCommand([
            '--action', 'lancar_e_conciliar',
            '--jogos', json_encode($jogos)
        ], $callback);
        
        return $data;
    }
}
