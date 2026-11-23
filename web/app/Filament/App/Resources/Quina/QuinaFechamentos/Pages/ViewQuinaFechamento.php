<?php

namespace App\Filament\App\Resources\Quina\QuinaFechamentos\Pages;

use App\Filament\App\Resources\Quina\QuinaFechamentos\QuinaFechamentoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewQuinaFechamento extends ViewRecord
{
    protected static string $resource = QuinaFechamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
