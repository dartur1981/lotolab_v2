<?php

namespace App\Filament\App\Resources\Megasena\MegasenaFechamentos\Pages;

use App\Filament\App\Resources\Megasena\MegasenaFechamentos\MegasenaFechamentoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMegasenaFechamentos extends ListRecords
{
    protected static string $resource = MegasenaFechamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
