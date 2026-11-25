<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\Lotofacil\LotofacilFechamentoJogo;
use App\Services\LotofacilBotService;
use Illuminate\Support\Facades\Log;

#[Signature('bot:run {action} {--ids=} {--lidos=} {--fechamento=} {--model=lotofacil}')]
#[Description('Executa o bot do ADB em background')]
class RunBotCommand extends Command
{
    public function handle(LotofacilBotService $bot)
    {
        $action = $this->argument('action');
        $idsStr = $this->option('ids');
        $fechamentoId = $this->option('fechamento');
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

                $this->info("Iniciando Lançamento de Jogos (Etapa 1 - sem conferência automática)...");

                $resultado = $bot->lancarJogos($jogosParaLancar, $recordIds, $modelType);

                // Garante no banco Laravel que os jogos lançados fiquem carimbados como Lançado (3),
                // mesmo se o callback em tempo real no Python falhar ou houver lançamento parcial.
                $idsLancados = $resultado['ids_lancados'] ?? null;
                if (is_array($idsLancados) && !empty($idsLancados)) {
                    foreach ($jogosValidosRecords as $record) {
                        if (in_array((int) $record->id, array_map('intval', $idsLancados), true)) {
                            $record->refresh();
                            if (!in_array((string) $record->status, ['3', '4'], true)) {
                                $record->update(['status' => '3']);
                            }
                        }
                    }
                } elseif (!empty($resultado['sucesso'])) {
                    foreach ($jogosValidosRecords as $record) {
                        $record->refresh();
                        if (!in_array((string) $record->status, ['3', '4'], true)) {
                            $record->update(['status' => '3']);
                        }
                    }
                }

                if (!empty($resultado['sucesso'])) {
                    $totalLancados = count($jogosParaLancar);
                    $msg = "\n--- LANÇAMENTO CONCLUÍDO (ETAPA 1) ---\n"
                         . "{$totalLancados} jogo(s) lançado(s) no carrinho (status: Lançado).\n"
                         . "Clique em 'Conferir Carrinho' quando desejar validar e conciliar os jogos.";
                    file_put_contents($logPath, $msg, FILE_APPEND);
                } else {
                    $motivo = $resultado['motivo_falha'] ?? "O robô não conseguiu lançar todos os jogos.";
                    throw new \Exception("Falha no Lançamento: {$motivo}");
                }
            } elseif ($action === 'conferir') {
                if (!$fechamentoId) {
                    throw new \Exception("ID do fechamento não fornecido para conferência do carrinho.");
                }

                $fechamentoClass = match ($modelType) {
                    'estrategia' => \App\Models\Lotofacil\EstrategiaFechamento::class,
                    'megasena' => \App\Models\Megasena\MegasenaFechamento::class,
                    'quina' => \App\Models\Quina\QuinaFechamento::class,
                    default => \App\Models\Lotofacil\LotofacilFechamento::class,
                };

                $fechamento = $fechamentoClass::find($fechamentoId);
                if (!$fechamento) {
                    throw new \Exception("Fechamento #{$fechamentoId} não encontrado (Modelo: {$modelType}).");
                }

                $apiModelType = ($modelType === 'estrategia') ? 'lotofacil' : $modelType;
                $this->info("Iniciando Conferência do Carrinho (Etapa 2) para Fechamento #{$fechamentoId}...");

                $dados = $bot->irELerCarrinho($apiModelType);
                if (empty($dados['foi_carrinho'])) {
                    $motivo = $dados['motivo_falha'] ?? "Não foi possível navegar para o carrinho no app da Caixa.";
                    throw new \Exception("Falha ao abrir carrinho: {$motivo}");
                }

                $jogosLidos = $dados['jogos_lidos'] ?? [];
                if (empty($jogosLidos)) {
                    throw new \Exception("Nenhum jogo foi encontrado no carrinho do app da Caixa.");
                }

                $resumo = $bot->conciliarFechamentoComCarrinho($fechamento, $jogosLidos);

                $msg = "\n--- CONFERÊNCIA DO CARRINHO CONCLUÍDA (ETAPA 2) ---\n"
                     . "Jogos lidos no carrinho: {$resumo['total_lidos']}\n"
                     . "Conferidos agora (status -> Apostado): {$resumo['conciliados_agora']}\n"
                     . "Já estavam Apostados: {$resumo['ja_apostados']}\n"
                     . "Divergentes (Lançados não encontrados): {$resumo['divergentes']}\n";
                if ($resumo['extras'] > 0) {
                    $msg .= "Aviso: {$resumo['extras']} jogo(s) no carrinho não pertencem a este fechamento.\n";
                }
                file_put_contents($logPath, $msg, FILE_APPEND);
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
