<?php

namespace App\Filament\Resources\Loterias\Pages;

use App\Filament\Resources\Loterias\LoteriaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLoterias extends ListRecords
{
    protected static string $resource = LoteriaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
