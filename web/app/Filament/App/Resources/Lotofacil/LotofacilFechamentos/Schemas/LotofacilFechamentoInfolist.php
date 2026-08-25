<?php

namespace App\Filament\App\Resources\Lotofacil\LotofacilFechamentos\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class LotofacilFechamentoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Infolists\Components\ViewEntry::make('resultado_estatisticas')
                    ->view('filament.app.infolists.components.fechamento-stats')
                    ->columnSpanFull()
                    ->hiddenLabel(),
                    
                \Filament\Infolists\Components\ViewEntry::make('bot_log')
                    ->view('filament.app.components.bot-log-embed')
                    ->columnSpanFull()
                    ->hiddenLabel(),
            ]);
    }
}
