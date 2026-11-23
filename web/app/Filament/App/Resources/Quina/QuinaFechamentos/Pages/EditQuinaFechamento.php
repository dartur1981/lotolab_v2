<?php

namespace App\Filament\App\Resources\Quina\QuinaFechamentos\Pages;

use App\Filament\App\Resources\Quina\QuinaFechamentos\QuinaFechamentoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQuinaFechamento extends EditRecord
{
    protected static string $resource = QuinaFechamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
