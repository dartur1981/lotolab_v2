<?php

namespace App\Filament\App\Pages;

use App\Models\ConfiguracaoApp;
use App\Services\LotofacilBotService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ParearDispositivo extends Page
{
    protected static ?string $slug = 'conectar-celular';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-device-phone-mobile';
    protected static ?string $navigationLabel = 'Conectar Celular';
    protected ?string $heading = 'Conectar Celular (ADB Wireless)';
    protected ?string $subheading = 'Assistente em 3 passos para parear, conectar e validar o aparelho Android usado pelo robô.';
    protected static ?int $navigationSort = 95;

    protected string $view = 'filament.app.pages.parear-dispositivo';

    public int $step = 1;

    public string $ip = '192.168.0.220';
    public string $portaPareamento = '';
    public string $codigoPareamento = '';
    public string $portaConexao = '';

    public array $devices = [];
    public ?array $ultimoTeste = null;
    public string $consoleOutput = 'Aguardando ação...';
    public bool $consoleErro = false;

    public function mount(): void
    {
        $ultimoIp = ConfiguracaoApp::where('chave', 'adb_ultimo_ip')->value('valor');
        if (!empty($ultimoIp)) {
            $this->ip = (string) $ultimoIp;
        }

        $this->atualizarDispositivos(silencioso: true);
    }

    public function irParaPasso(int $passo): void
    {
        $this->step = max(1, min(3, $passo));
    }

    protected function salvarUltimoIp(): void
    {
        $ipLimpo = trim($this->ip);
        if ($ipLimpo !== '') {
            ConfiguracaoApp::updateOrCreate(
                ['chave' => 'adb_ultimo_ip'],
                ['valor' => $ipLimpo]
            );
        }
    }

    public function atualizarDispositivos(bool $silencioso = false): void
    {
        try {
            $bot = app(LotofacilBotService::class);
            $this->devices = $bot->adbDevices();
            $this->consoleErro = false;

            if (!$silencioso) {
                $qtd = count($this->devices);
                $this->consoleOutput = "Consulta ADB concluída: {$qtd} dispositivo(s) encontrado(s).";
                Notification::make()
                    ->title("{$qtd} dispositivo(s) encontrado(s)")
                    ->info()
                    ->send();
            }
        } catch (\Throwable $e) {
            $this->devices = [];
            $this->consoleErro = true;
            $this->consoleOutput = $e->getMessage();

            if (!$silencioso) {
                Notification::make()
                    ->title('Erro ao consultar ADB')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
            }
        }
    }

    public function parear(): void
    {
        $ip = trim($this->ip);
        $porta = trim($this->portaPareamento);
        $codigo = preg_replace('/\D+/', '', $this->codigoPareamento);

        if ($ip === '' || $porta === '' || $codigo === '') {
            Notification::make()
                ->title('Preencha IP, Porta de Pareamento e Código (6 dígitos).')
                ->warning()
                ->send();
            return;
        }

        $this->salvarUltimoIp();

        try {
            $bot = app(LotofacilBotService::class);
            $res = $bot->adbPair($ip, $porta, $codigo);
            $saida = (string) ($res['saida'] ?? '');
            $sucesso = !empty($res['sucesso']);

            $this->consoleOutput = $saida;
            $this->consoleErro = !$sucesso;

            if ($sucesso) {
                $this->codigoPareamento = '';
                $this->portaPareamento = '';
                $this->step = 2;

                Notification::make()
                    ->title('Celular pareado com sucesso!')
                    ->body('Agora informe a Porta de Conexão na Etapa 2 para conectar.')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Não foi possível parear')
                    ->body($saida ?: 'Verifique IP, porta e código de 6 dígitos.')
                    ->danger()
                    ->send();
            }
        } catch (\Throwable $e) {
            $this->consoleErro = true;
            $this->consoleOutput = $e->getMessage();

            Notification::make()
                ->title('Erro ao parear')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function conectar(): void
    {
        $ip = trim($this->ip);
        $porta = trim($this->portaConexao);

        if ($ip === '' || $porta === '') {
            Notification::make()
                ->title('Preencha o IP e a Porta de Conexão.')
                ->warning()
                ->send();
            return;
        }

        $this->salvarUltimoIp();

        try {
            $bot = app(LotofacilBotService::class);
            $res = $bot->adbConnect($ip, $porta);
            $saida = (string) ($res['saida'] ?? '');
            $sucesso = !empty($res['sucesso']);

            $this->devices = $res['devices'] ?? $this->devices;
            $this->consoleOutput = $saida;
            $this->consoleErro = !$sucesso;

            if ($sucesso) {
                $target = "{$ip}:{$porta}";
                $this->testar($target, avancarParaPasso3: true);
            } else {
                Notification::make()
                    ->title('Falha ao conectar')
                    ->body($saida ?: 'Verifique se a Depuração por Wi-Fi está ativa e a porta está correta.')
                    ->danger()
                    ->send();
            }
        } catch (\Throwable $e) {
            $this->consoleErro = true;
            $this->consoleOutput = $e->getMessage();

            Notification::make()
                ->title('Erro ao conectar')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function testar(?string $serial = null, bool $avancarParaPasso3 = false): void
    {
        try {
            $bot = app(LotofacilBotService::class);
            $res = $bot->adbTest($serial);

            $this->devices = $res['devices'] ?? $this->devices;
            $this->ultimoTeste = $res;
            $this->consoleOutput = (string) ($res['saida'] ?? '');
            $this->consoleErro = empty($res['sucesso']);

            if (!empty($res['sucesso'])) {
                $this->step = 3;
                Notification::make()
                    ->title('Aparelho validado e pronto para o robô!')
                    ->body(($res['model'] ?? '') . ' (' . ($res['serial'] ?? '') . ')')
                    ->success()
                    ->send();
            } else {
                if ($avancarParaPasso3) {
                    $this->step = 3;
                }
                Notification::make()
                    ->title('Conexão ADB ok, mas o teste do robô falhou')
                    ->body($res['saida'] ?? 'Verifique a tela do celular.')
                    ->warning()
                    ->send();
            }
        } catch (\Throwable $e) {
            $this->consoleErro = true;
            $this->consoleOutput = $e->getMessage();

            Notification::make()
                ->title('Erro ao validar dispositivo')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function desconectar(?string $serial = null): void
    {
        try {
            $bot = app(LotofacilBotService::class);
            $res = $bot->adbDisconnect($serial);

            $this->devices = $res['devices'] ?? [];
            $this->ultimoTeste = null;
            $this->consoleOutput = (string) ($res['saida'] ?? 'Desconectado.');
            $this->consoleErro = false;

            Notification::make()
                ->title($serial ? "Dispositivo {$serial} desconectado." : 'Todos os dispositivos desconectados.')
                ->info()
                ->send();
        } catch (\Throwable $e) {
            $this->consoleErro = true;
            $this->consoleOutput = $e->getMessage();

            Notification::make()
                ->title('Erro ao desconectar')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
