<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\File;

class BotLogViewerWidget extends Widget
{
    protected string $view = 'livewire.bot-log-viewer';

    public $logContent = '';

    protected int | string | array $columnSpan = 'full';

    public function updateLogs()
    {
        $logFile = base_path('../logs/bot_lotofacil.log');
        
        if (File::exists($logFile)) {
            $content = File::get($logFile);
            
            if (str_contains($content, '[BOT_FINISHED_SUCCESS]')) {
                // Remove a tag para não ficar disparando a notificação infinitamente, mas MANTÉM o arquivo!
                $content = str_replace('[BOT_FINISHED_SUCCESS]', '', $content);
                File::put($logFile, $content);
                $this->logContent = trim($content);
                
                \Filament\Notifications\Notification::make()
                    ->title('Robô Finalizado')
                    ->body('Os lançamentos foram processados com sucesso!')
                    ->success()
                    ->send();
                    
                $this->dispatch('refresh-jogos-table');
            } elseif (str_contains($content, '[BOT_FINISHED_ERROR]')) {
                // Remove the marker so it doesn't trigger repeatedly, but KEEP the file so the user can read the error!
                $content = str_replace('[BOT_FINISHED_ERROR]', '', $content);
                File::put($logFile, $content);
                $this->logContent = trim($content);
                
                \Filament\Notifications\Notification::make()
                    ->title('Erro no Robô')
                    ->body('O processo falhou. Verifique o log na tela para mais detalhes.')
                    ->danger()
                    ->send();
                    
                $this->dispatch('refresh-jogos-table');
            } else {
                $this->logContent = trim($content);
            }
        } else {
            $this->logContent = '';
        }
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $this->updateLogs();
        return parent::render();
    }
}

