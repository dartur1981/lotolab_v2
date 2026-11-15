<?php

namespace App\Filament\Concerns;

use Filament\Facades\Filament;

trait BelongsToLotofacil
{
    public static function canViewAny(): bool
    {
        return Filament::getTenant()?->slug === 'lotofacil';
    }

    public static function canAccess(): bool
    {
        return Filament::getTenant()?->slug === 'lotofacil';
    }

    public static function canAccessClusteredComponents(): bool
    {
        return Filament::getTenant()?->slug === 'lotofacil';
    }
}
