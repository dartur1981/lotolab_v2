<?php

namespace App\Filament\App\Resources\Megasena\MegasenaFechamentos\Schemas;

use Filament\Schemas\Schema;

class MegasenaFechamentoInfolist
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
