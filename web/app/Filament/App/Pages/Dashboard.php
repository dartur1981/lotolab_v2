<?php

namespace App\Filament\App\Pages;

use Filament\Facades\Filament;
use Filament\Pages\Dashboard as BaseDashboard;
use App\Livewire\MegasenaCiclosWidget;
use App\Livewire\CiclosWidget;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;

class Dashboard extends BaseDashboard
{
    /**
     * @return array<class-string<\Filament\Widgets\Widget> | \Filament\Widgets\WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        $tenant = Filament::getTenant();

        if ($tenant?->slug === 'megasena') {
            return [
                MegasenaCiclosWidget::class,
            ];
        }

        return [
            AccountWidget::class,
            FilamentInfoWidget::class,
            CiclosWidget::class,
        ];
    }
}
