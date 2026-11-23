<?php

namespace App\Filament\App\Resources\Quina\Pages;

use App\Filament\App\Resources\Quina\BolaoResource;
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
