<?php

namespace App\Filament\Concerns;

use Filament\Facades\Filament;

trait BelongsToMegasena
{
    public static function canViewAny(): bool
    {
        return Filament::getTenant()?->slug === 'megasena';
    }

    public static function canAccess(): bool
    {
        return Filament::getTenant()?->slug === 'megasena';
    }

    public static function canAccessClusteredComponents(): bool
    {
        return Filament::getTenant()?->slug === 'megasena';
    }
}
