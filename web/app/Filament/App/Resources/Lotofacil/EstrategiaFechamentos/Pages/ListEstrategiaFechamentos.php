<?php

namespace App\Filament\App\Resources\Lotofacil\EstrategiaFechamentos\Pages;

use App\Filament\App\Resources\Lotofacil\EstrategiaFechamentos\EstrategiaFechamentoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEstrategiaFechamentos extends ListRecords
{
    protected static string $resource = EstrategiaFechamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getMaxContentWidth(): \Filament\Support\Enums\Width | string | null
    {
        return \Filament\Support\Enums\Width::Full;
    }
}
