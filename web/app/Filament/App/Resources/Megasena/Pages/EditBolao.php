<?php

namespace App\Filament\App\Resources\Megasena\Pages;

use App\Filament\App\Resources\Megasena\BolaoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBolao extends EditRecord
{
    protected static string $resource = BolaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
