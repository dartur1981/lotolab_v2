<?php

namespace App\Filament\App\Resources\Lotofacil\LotofacilFechamentos\Pages;

use App\Filament\App\Resources\Lotofacil\LotofacilFechamentos\LotofacilFechamentoResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditLotofacilFechamento extends EditRecord
{
    protected static string $resource = LotofacilFechamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }


}
