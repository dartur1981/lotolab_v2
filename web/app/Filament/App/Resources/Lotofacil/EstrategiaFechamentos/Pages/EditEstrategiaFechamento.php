<?php

namespace App\Filament\App\Resources\Lotofacil\EstrategiaFechamentos\Pages;

use App\Filament\App\Resources\Lotofacil\EstrategiaFechamentos\EstrategiaFechamentoResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditEstrategiaFechamento extends EditRecord
{
    protected static string $resource = EstrategiaFechamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
