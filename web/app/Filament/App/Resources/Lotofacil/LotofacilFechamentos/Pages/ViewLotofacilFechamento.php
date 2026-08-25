<?php

namespace App\Filament\App\Resources\Lotofacil\LotofacilFechamentos\Pages;

use App\Filament\App\Resources\Lotofacil\LotofacilFechamentos\LotofacilFechamentoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLotofacilFechamento extends ViewRecord
{
    protected static string $resource = LotofacilFechamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }


}
