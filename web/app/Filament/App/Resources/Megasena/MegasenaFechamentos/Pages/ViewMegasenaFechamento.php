<?php

namespace App\Filament\App\Resources\Megasena\MegasenaFechamentos\Pages;

use App\Filament\App\Resources\Megasena\MegasenaFechamentos\MegasenaFechamentoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMegasenaFechamento extends ViewRecord
{
    protected static string $resource = MegasenaFechamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
