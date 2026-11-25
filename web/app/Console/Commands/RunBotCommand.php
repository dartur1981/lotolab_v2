<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\Lotofacil\LotofacilFechamentoJogo;
use App\Services\LotofacilBotService;
use Illuminate\Support\Facades\Log;

#[Signature('bot:run {action} {--ids=} {--lidos=} {--model=lotofacil}')]
#[Description('Executa o bot do ADB em background')]
class RunBotCommand extends Command
{
    public function handle(LotofacilBotService $bot)
    {
        $action = $this->argument('action');
        $idsStr = $this->option('ids');
        $modelType = $this->option('model') ?: 'lotofacil';
        $logFileName = match ($modelType) {
            'megasena' => 'bot_megasena.log',
            'quina' => 'bot_quina.log',
            default => 'bot_lotofacil.log',
        };
        $logPath = base_path('../logs/' . $logFileName);
        
        $inicioProcesso = now();
        $inicioMicro = microtime(true);

        $apiUrl = $bot->getApiUrl();
        $this->info("Conectando à API Python: {$apiUrl}");
        Log::info("RunBotCommand iniciado com ação: {$action}, IDs: {$idsStr}, Modelo: {$modelType}, API: {$apiUrl}");
        
        $headerLog = "======================================================\n" .
                     "              INICIALIZAÇÃO DO ROBÔ\n" .
                     "======================================================\n" .
                     "Ação: {$action} | Modelo: {$modelType}\n" .
                     "Horário de Início : " . $inicioProcesso->format('d/m/Y H:i:s') . "\n" .
                     "Conectando à API Python: {$apiUrl}\n" .
                     "======================================================\n\n";
        file_put_contents($logPath, $headerLog);
        @chmod($logPath, 0666);

        try {
            if ($action === 'lancar') {
                if (!$idsStr) {
                    throw new \Exception("IDs não fornecidos.");
                }
                
                $ids = array_filter(array_map('trim', explode(',', $idsStr)));
                if ($modelType === 'estrategia') {
                    $modelClass = \App\Models\Lotofacil\EstrategiaFechamentoJogo::class;
                    $fechamentoCol = 'estrategia_fechamento_id';
                } elseif ($modelType === 'megasena') {
                    $modelClass = \App\Models\Megasena\MegasenaFechamentoJogo::class;
                    $fechamentoCol = 'fechamento_id';
                } elseif ($modelType === 'quina') {
                    $modelClass = \App\Models\Quina\QuinaFechamentoJogo::class;
                    $fechamentoCol = 'fechamento_id';
                } else {
                    $modelClass = LotofacilFechamentoJogo::class;
                    $fechamentoCol = 'fechamento_id';
                }

                $jogosRecords = $modelClass::whereIn('id', $ids)->get();

                if ($jogosRecords->isEmpty()) {
                    throw new \Exception("Nenhum registro de jogo encontrado no banco para os IDs: {$idsStr} (Modelo: {$modelType})");
                }
                
                $jogosParaLancar = [];
                $jogosValidosRecords = [];
                foreach ($jogosRecords as $record) {
                    $dezenas = LotofacilBotService::parseDezenas($record->dezenas);
                    if (!empty($dezenas)) {
                        $jogosParaLancar[] = $dezenas;
                        $jogosValidosRecords[] = $record;
                    }
                }
                
                if (empty($jogosParaLancar)) {
                    throw new \Exception("Nenhuma dezena válida encontrada nos jogos selecionados (IDs: {$idsStr}).");
                }

                $recordIds = array_map(fn ($r) => $r->id, $jogosValidosRecords);

                // Busca jogos anteriores do mesmo fechamento que já estão Lançados (3) ou Apostados (4)
                // para evitar falsos positivos de "jogos extras" e recuperar jogos presos em Lançado (3)
                $fechamentoIds = collect($jogosValidosRecords)
                    ->pluck($fechamentoCol)
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();

                $jogosAnterioresRecords = [];
                $jogosAnterioresDezenas = [];
                if (!empty($fechamentoIds)) {
                    $anterioresQuery = $modelClass::whereIn($fechamentoCol, $fechamentoIds)
                        ->whereNotIn('id', $recordIds)
                        ->whereIn('status', ['3', '4'])
                        ->orderByRaw("CASE WHEN status = '4' THEN 0 ELSE 1 END")
                        ->get();

                    foreach ($anterioresQuery as $antRecord) {
                        $antDezenas = LotofacilBotService::parseDezenas($antRecord->dezenas);
                        if (!empty($antDezenas)) {
                            $jogosAnterioresRecords[] = $antRecord;
                            $jogosAnterioresDezenas[] = $antDezenas;
                        }
                    }
                }

                $this->info("Iniciando Lançamento e Conciliação no Carrinho num único processo...");
                
                $resultado = $bot->lancarEConciliar($jogosParaLancar, null, $recordIds, $modelType, $jogosAnterioresDezenas);
                    
                    if (!empty($resultado['sucesso_lancamento'])) {
                        
                        if (!empty($resultado['foi_carrinho']) && !empty($resultado['conciliacao'])) {
                            $conciliacao = $resultado['conciliacao'];
                            $qtdOk = 0;
                            $qtdDivergente = 0;
                            $qtdRecuperados = 0;
                            $qtdJaApostados = 0;
                            
                            foreach ($jogosValidosRecords as $index => $record) {
                                if (isset($conciliacao['jogos_processados'][$index])) {
                                    $proc = $conciliacao['jogos_processados'][$index];
                                    if ($proc && $proc['status'] === 'OK') {
                                        $record->update(['status' => '4']); // 4 = Apostado (conciliado)
                                        $qtdOk++;
                                    } else {
                                        $record->update(['status' => '1']); // 1 = Divergente
                                        $qtdDivergente++;
                                    }
                                }
                            }

                            // Concilia também jogos anteriores do mesmo fechamento (ex: presos em Lançado=3 após queda)
                            $anterioresProc = $conciliacao['jogos_anteriores_processados'] ?? [];
                            foreach ($jogosAnterioresRecords as $index => $antRecord) {
                                if (!isset($anterioresProc[$index])) {
                                    continue;
                                }
                                $proc = $anterioresProc[$index];
                                $statusAtual = (string) $antRecord->status;

                                if ($proc && $proc['status'] === 'OK') {
                                    if ($statusAtual === '3') {
                                        $antRecord->update(['status' => '4']);
                                        $qtdRecuperados++;
                                    } else {
                                        $qtdJaApostados++;
                                    }
                                } elseif ($statusAtual === '3') {
                                    $antRecord->update(['status' => '1']);
                                    $qtdDivergente++;
                                }
                            }
                            
                            $msg = "\n--- FASE 2 CONCLUIDA ---\nResumo do lote atual: {$qtdOk} OK, {$qtdDivergente} Divergentes.";
                            if ($qtdRecuperados > 0) {
                                $msg .= "\nRecuperados de execução anterior (Lançado -> Apostado): {$qtdRecuperados} jogo(s) conciliado(s) no carrinho.";
                            }
                            if ($qtdJaApostados > 0) {
                                $msg .= "\nJá apostados anteriormente no carrinho: {$qtdJaApostados} jogo(s).";
                            }
                            if (!empty($conciliacao['jogos_extras'])) {
                                $msg .= "\nAviso: Foram encontrados " . count($conciliacao['jogos_extras']) . " jogos extras no carrinho!";
                            }
                            file_put_contents($logPath, $msg, FILE_APPEND);
                        } else {
                            $motivo = $resultado['motivo_falha'] ?? "Falha ao navegar para o carrinho na Fase 2 (ou ler o carrinho).";
                            throw new \Exception("Fase 1 OK, mas falha na Fase 2: {$motivo}");
                        }
                    } else {
                        $motivo = $resultado['motivo_falha'] ?? "O robô não conseguiu lançar todos os jogos.";
                        throw new \Exception("Falha na Fase 1: {$motivo}");
                    }
            } elseif ($action === 'carrinho') {
                $bot->irParaCarrinho();
            } elseif ($action === 'ler') {
                $bot->lerCarrinho();
            }
            
            $fimProcesso = now();
            $duracaoSegundos = microtime(true) - $inicioMicro;
            $horas = floor($duracaoSegundos / 3600);
            $minutos = floor(($duracaoSegundos % 3600) / 60);
            $segundos = $duracaoSegundos - ($horas * 3600) - ($minutos * 60);
            $tempoFormatado = sprintf('%02d:%02d:%05.2f', $horas, $minutos, $segundos);

            $resumoTempo = "\n\n" .
                "======================================================\n" .
                "            RESUMO DE TEMPO DO PROCESSO\n" .
                "======================================================\n" .
                "Horário de Início : " . $inicioProcesso->format('d/m/Y H:i:s') . "\n" .
                "Horário de Término: " . $fimProcesso->format('d/m/Y H:i:s') . "\n" .
                "Tempo Total Levado: " . $tempoFormatado . " (" . number_format($duracaoSegundos, 2, ',', '.') . "s)\n" .
                "======================================================\n";

            // Append completion marker to log file so Livewire knows we are done
            file_put_contents($logPath, $resumoTempo . "\n[BOT_FINISHED_SUCCESS]", FILE_APPEND);
            
        } catch (\Throwable $e) {
            $fimProcesso = now();
            $duracaoSegundos = microtime(true) - $inicioMicro;
            $horas = floor($duracaoSegundos / 3600);
            $minutos = floor(($duracaoSegundos % 3600) / 60);
            $segundos = $duracaoSegundos - ($horas * 3600) - ($minutos * 60);
            $tempoFormatado = sprintf('%02d:%02d:%05.2f', $horas, $minutos, $segundos);

            $resumoTempoErro = "\n\n" .
                "======================================================\n" .
                "        RESUMO DE TEMPO DO PROCESSO (INTERROMPIDO)\n" .
                "======================================================\n" .
                "Horário de Início : " . $inicioProcesso->format('d/m/Y H:i:s') . "\n" .
                "Horário de Término: " . $fimProcesso->format('d/m/Y H:i:s') . "\n" .
                "Tempo Total Levado: " . $tempoFormatado . " (" . number_format($duracaoSegundos, 2, ',', '.') . "s)\n" .
                "======================================================\n";

            Log::error("BotCommand Erro: " . $e->getMessage());
            $this->error($e->getMessage());
            // Append error marker and the actual error message
            $errorMessage = "\n\nERRO FATAL PHP/PYTHON:\n" . $e->getMessage();
            file_put_contents($logPath, $errorMessage . $resumoTempoErro . "\n[BOT_FINISHED_ERROR]", FILE_APPEND);
        } finally {
            $lockFile = base_path('../logs/bot.lock');
            if (file_exists($lockFile)) {
                unlink($lockFile);
            }
        }
    }
}
