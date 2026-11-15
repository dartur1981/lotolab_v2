<?php

namespace App\Filament\App\Resources\Megasena\MegasenaFechamentos\Pages;

use App\Filament\App\Resources\Megasena\MegasenaFechamentos\MegasenaFechamentoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMegasenaFechamento extends EditRecord
{
    protected static string $resource = MegasenaFechamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
