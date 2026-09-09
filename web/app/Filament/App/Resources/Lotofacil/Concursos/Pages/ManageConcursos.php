<?php

namespace App\Filament\App\Resources\Lotofacil\Concursos\Pages;

use App\Filament\App\Resources\Lotofacil\Concursos\ConcursoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageConcursos extends ManageRecords
{
    protected static string $resource = ConcursoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\ImportAction::make()
                ->importer(\App\Filament\Imports\Lotofacil\ConcursoImporter::class)
                ->label('Importar Histórico (Excel/CSV)'),
            CreateAction::make(),
        ];
    }
}
