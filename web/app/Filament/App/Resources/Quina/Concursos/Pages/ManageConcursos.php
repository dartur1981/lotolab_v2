<?php

namespace App\Filament\App\Resources\Quina\Concursos\Pages;

use App\Filament\App\Resources\Quina\Concursos\ConcursoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageConcursos extends ManageRecords
{
    protected static string $resource = ConcursoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
