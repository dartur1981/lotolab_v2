<?php

namespace App\Livewire;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PrevisoesMLWidget extends Widget
{
    protected string $view = 'livewire.previsoes-m-l-widget';
    protected int | string | array $columnSpan = 'full';
    
    public array $previsoes = [];
    public bool $erro = false;
    public string $mensagem_erro = '';

    public function mount()
    {
        $this->carregarPrevisoes();
    }

    public function carregarPrevisoes()
    {
        try {
            $response = Http::timeout(10)->get('http://127.0.0.1:5000/prever-sorteio');
            if ($response->successful()) {
                $this->previsoes = $response->json('previsoes') ?? [];
                $this->erro = false;
            } else {
                $this->erro = true;
                $this->mensagem_erro = $response->json('detail') ?? $response->json('message') ?? 'Erro ao consultar IA';
            }
        } catch (\Exception $e) {
            $this->erro = true;
            $this->mensagem_erro = "API Python Offline ou falha de comunicação.";
            Log::error('Erro ML: ' . $e->getMessage());
        }
    }
}
