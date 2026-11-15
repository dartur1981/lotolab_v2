<?php

namespace App\Filament\App\Resources\Megasena\Pages;

use App\Filament\App\Resources\Megasena\BolaoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBolaos extends ListRecords
{
    protected static string $resource = BolaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
