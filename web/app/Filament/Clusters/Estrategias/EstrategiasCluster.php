<?php

namespace App\Filament\Clusters\Estrategias;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class EstrategiasCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-light-bulb';
    protected static string|\UnitEnum|null $navigationGroup = 'Lotofácil';
    protected static ?int $navigationSort = 100;
    protected static ?string $navigationLabel = 'Estratégias';
    protected static ?string $slug = 'estrategias';

    public static function getNavigationUrl(): string
    {
        return \App\Filament\Clusters\Estrategias\Pages\Estrategias::getUrl();
    }

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null): string
    {
        return \App\Filament\Clusters\Estrategias\Pages\Estrategias::getUrl($parameters, $isAbsolute, $panel, $tenant);
    }

    public function mount(): void
    {
        $this->redirect(\App\Filament\Clusters\Estrategias\Pages\Estrategias::getUrl(), navigate: false);
    }
}

