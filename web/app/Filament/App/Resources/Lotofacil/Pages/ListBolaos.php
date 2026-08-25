<?php

namespace App\Filament\App\Resources\Lotofacil\Pages;

use App\Filament\App\Resources\Lotofacil\BolaoResource;
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

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\App\Resources\Lotofacil\Widgets\BolaoStatsWidget::class,
        ];
    }

    public function getMaxContentWidth(): \Filament\Support\Enums\Width | string | null
    {
        return \Filament\Support\Enums\Width::Full;
    }
}
