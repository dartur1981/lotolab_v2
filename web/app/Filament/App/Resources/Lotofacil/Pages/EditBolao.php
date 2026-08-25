<?php

namespace App\Filament\App\Resources\Lotofacil\Pages;

use App\Filament\App\Resources\Lotofacil\BolaoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBolao extends EditRecord
{
    protected static string $resource = BolaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
