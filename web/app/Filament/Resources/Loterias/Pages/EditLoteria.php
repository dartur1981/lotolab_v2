<?php

namespace App\Filament\Resources\Loterias\Pages;

use App\Filament\Resources\Loterias\LoteriaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLoteria extends EditRecord
{
    protected static string $resource = LoteriaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
