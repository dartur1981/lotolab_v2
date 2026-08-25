<?php

namespace App\Filament\App\Resources\Lotofacil\Widgets;

use App\Models\Lotofacil\Bolao;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BolaoStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $valorTotalEncerrado = Bolao::where('status', 2)->sum('valor_total');
        $rateioTotalEncerrado = Bolao::where('status', 2)->sum('valor_cota');
        $quantidadeJogosEncerrado = \App\Models\Lotofacil\LotofacilFechamento::whereHas('bolao', function ($query) {
            $query->where('status', 2);
        })->sum('quantidade_jogos');
        
        return [
            Stat::make('Total Arrecadado (Encerrados)', 'R$ ' . number_format((float) $valorTotalEncerrado, 2, ',', '.'))
                ->description('Valor total dos bolões com status Encerrado')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
            Stat::make('Soma do Rateio (Encerrados)', 'R$ ' . number_format((float) $rateioTotalEncerrado, 2, ',', '.'))
                ->description('Soma do rateio dos bolões encerrados')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),
            Stat::make('Jogos Gerados (Encerrados)', number_format((int) $quantidadeJogosEncerrado, 0, ',', '.'))
                ->description('Quantidade de jogos gerados nos bolões encerrados')
                ->descriptionIcon('heroicon-m-ticket')
                ->color('warning'),
        ];
    }
}
