<?php

namespace App\Filament\Clusters\Estrategias;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class EstrategiasCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-light-bulb';
    protected static string|\UnitEnum|null $navigationGroup = 'Lotofácil';
    protected static ?int $navigationSort = 100;
    protected static ?string $navigationLabel = 'Estratégias';
    protected static ?string $slug = 'estrategias';
}
