<?php

namespace App\Filament\Concerns;

use Filament\Facades\Filament;

trait BelongsToQuina
{
    public static function canViewAny(): bool
    {
        if (Filament::getTenant()?->slug !== 'quina') {
            return false;
        }

        $user = auth()->user();
        if (!$user) {
            return false;
        }

        $permission = 'View:' . class_basename(static::class);
        if (\Spatie\Permission\Models\Permission::where('name', $permission)->exists()) {
            return $user->can($permission);
        }

        return true;
    }

    public static function canAccess(): bool
    {
        if (Filament::getTenant()?->slug !== 'quina') {
            return false;
        }

        $user = auth()->user();
        if (!$user) {
            return false;
        }

        $permission = 'View:' . class_basename(static::class);
        if (\Spatie\Permission\Models\Permission::where('name', $permission)->exists()) {
            return $user->can($permission);
        }

        return true;
    }

    public static function canAccessClusteredComponents(): bool
    {
        return static::canAccess();
    }
}
