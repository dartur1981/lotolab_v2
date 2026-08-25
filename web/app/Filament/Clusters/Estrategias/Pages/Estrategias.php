<?php

namespace App\Filament\Clusters\Estrategias\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use App\Livewire\TrincasWidget;
use App\Livewire\PadroesWidget;
use App\Livewire\PrevisoesMLWidget;

class Estrategias extends Page
{
    protected static ?string $cluster = \App\Filament\Clusters\Estrategias\EstrategiasCluster::class;
    protected ?string $heading = 'Visão Geral das Estratégias';
    protected static ?string $navigationLabel = 'Visão Geral';
    
    protected string $view = 'filament.app.pages.estrategias';

    public function getMaxContentWidth(): \Filament\Support\Enums\Width | string | null
    {
        return 'full';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PrevisoesMLWidget::class,
            TrincasWidget::class,
            PadroesWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('treinar_modelo')
                ->label('Treinar IA (Random Forest)')
                ->icon('heroicon-o-cpu-chip')
                ->color('success')
                ->action(function () {
                    try {
                        $response = Http::timeout(120)->post('http://127.0.0.1:5000/treinar-modelo');
                        if ($response->successful()) {
                            $acc = $response->json('acuracia');
                            Notification::make()
                                ->title('IA Treinada!')
                                ->body("Acurácia no teste: {$acc}%")
                                ->success()
                                ->send();
                            return redirect(request()->header('Referer'));
                        } else {
                            throw new \Exception($response->json('message') ?? 'Erro desconhecido');
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Erro ao treinar IA')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('sincronizar')
                ->label('Sincronizar IA (Trincas e Padrões)')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action(function () {
                    try {
                        $response = Http::timeout(120)->post('http://127.0.0.1:5000/atualizar-estrategias');
                        
                        if ($response->successful()) {
                            Notification::make()
                                ->title('Estatísticas Sincronizadas!')
                                ->success()
                                ->send();
                            
                            // Recarrega a página para atualizar as tabelas/widgets que criaremos
                            return redirect(request()->header('Referer'));
                        } else {
                            throw new \Exception($response->json('message') ?? 'Erro desconhecido');
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Erro ao sincronizar com Python')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
