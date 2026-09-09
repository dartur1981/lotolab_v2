<?php

namespace App\Filament\App\Resources\Lotofacil\EstrategiaFechamentos\Pages;

use App\Filament\App\Resources\Lotofacil\EstrategiaFechamentos\EstrategiaFechamentoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEstrategiaFechamento extends ViewRecord
{
    protected static string $resource = EstrategiaFechamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public function getMaxContentWidth(): \Filament\Support\Enums\Width | string | null
    {
        return \Filament\Support\Enums\Width::Full;
    }
}
