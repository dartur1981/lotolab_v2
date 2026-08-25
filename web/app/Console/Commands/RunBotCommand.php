<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\Lotofacil\LotofacilFechamentoJogo;
use App\Services\LotofacilBotService;
use Illuminate\Support\Facades\Log;

#[Signature('bot:run {action} {--ids=} {--lidos=}')]
#[Description('Executa o bot do ADB em background')]
class RunBotCommand extends Command
{
    public function handle(LotofacilBotService $bot)
    {
        $action = $this->argument('action');
        $idsStr = $this->option('ids');
        
        try {
            if ($action === 'lancar') {
                if (!$idsStr) {
                    $this->error("IDs não fornecidos.");
                    return;
                }
                
                $ids = explode(',', $idsStr);
                $jogosRecords = LotofacilFechamentoJogo::whereIn('id', $ids)->get();
                
                $jogosParaLancar = [];
                foreach ($jogosRecords as $record) {
                    $val = $record->dezenas;
                    if (is_string($val)) {
                        $decoded = json_decode($val, true);
                        if (is_array($decoded)) {
                            $dezenas = array_map('intval', $decoded);
                        } else {
                            $dezenas = array_map('intval', explode(',', $val));
                        }
                    } else if (is_array($val)) {
                        $dezenas = array_map('intval', $val);
                    } else {
                        $dezenas = [];
                    }
                    if (!empty($dezenas)) {
                        $jogosParaLancar[] = $dezenas;
                    }
                }
                
                if (!empty($jogosParaLancar)) {
                    $this->info("Iniciando Lançamento e Conciliação no Carrinho num único processo...");
                    
                    $resultado = $bot->lancarEConciliar($jogosParaLancar, function ($type, $buffer) use ($jogosRecords) {
                        // O Python emite: "Jogo X adicionado com sucesso ao carrinho"
                        if (preg_match('/Jogo (\d+) adicionado com sucesso/i', $buffer, $matches)) {
                            $index = (int)$matches[1] - 1; // Python printa 1-based, array é 0-based
                            if (isset($jogosRecords[$index])) {
                                // Atualiza para 3 (Lançado) em tempo real!
                                $jogosRecords[$index]->update(['status' => '3']);
                            }
                        }
                    });
                    
                    if (!empty($resultado['sucesso_lancamento'])) {
                        
                        if (!empty($resultado['foi_carrinho']) && !empty($resultado['conciliacao'])) {
                            $conciliacao = $resultado['conciliacao'];
                            $qtdOk = 0;
                            $qtdDivergente = 0;
                            
                            foreach ($jogosRecords as $index => $record) {
                                if (isset($resultado['conciliacao']['jogos_processados'][$index])) {
                                    $proc = $resultado['conciliacao']['jogos_processados'][$index];
                                    if ($proc && $proc['status'] === 'OK') {
                                        $record->update(['status' => '4']); // 4 = Apostado (conciliado)
                                        $qtdOk++;
                                    } else {
                                        $record->update(['status' => '1']); // 1 = Divergente
                                        $qtdDivergente++;
                                    }
                                }
                            }
                            
                            $msg = "\n--- FASE 2 CONCLUIDA ---\nResumo: {$qtdOk} OK, {$qtdDivergente} Divergentes.";
                            if (!empty($conciliacao['jogos_extras'])) {
                                $msg .= "\nAviso: Foram encontrados " . count($conciliacao['jogos_extras']) . " jogos extras no carrinho!";
                            }
                            file_put_contents(base_path('../logs/bot_lotofacil.log'), $msg, FILE_APPEND);
                        } else {
                            throw new \Exception("Fase 1 OK, mas falha ao navegar para o carrinho na Fase 2 (ou ler o carrinho).");
                        }
                    } else {
                        throw new \Exception("Falha na Fase 1. O robô não conseguiu lançar todos os jogos.");
                    }
                }
            } elseif ($action === 'carrinho') {
                $bot->irParaCarrinho();
            } elseif ($action === 'ler') {
                $bot->lerCarrinho();
            }
            
            // Append completion marker to log file so Livewire knows we are done
            file_put_contents(base_path('../logs/bot_lotofacil.log'), "\n[BOT_FINISHED_SUCCESS]", FILE_APPEND);
            
        } catch (\Throwable $e) {
            Log::error("BotCommand Erro: " . $e->getMessage());
            $this->error($e->getMessage());
            // Append error marker and the actual error message
            $errorMessage = "\n\nERRO FATAL PHP/PYTHON:\n" . $e->getMessage();
            file_put_contents(base_path('../logs/bot_lotofacil.log'), $errorMessage . "\n[BOT_FINISHED_ERROR]", FILE_APPEND);
        } finally {
            $lockFile = base_path('../logs/bot.lock');
            if (file_exists($lockFile)) {
                unlink($lockFile);
            }
        }
    }
}
