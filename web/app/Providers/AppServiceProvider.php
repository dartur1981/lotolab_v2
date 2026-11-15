<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (
            str_starts_with(config('app.url'), 'https://') ||
            request()->header('X-Forwarded-Proto') === 'https' ||
            request()->server('HTTP_X_FORWARDED_PROTO') === 'https' ||
            request()->server('HTTPS') === 'on'
        ) {
            URL::forceScheme('https');
        }

        Gate::before(function ($user, $ability) {
            return $user->hasRole(config('filament-shield.super_admin.name', 'super_admin')) ? true : null;
        });

        // Registro de Policies dos Recursos Lotofácil
        Gate::policy(\App\Models\Lotofacil\Bolao::class, \App\Policies\Lotofacil\BolaoPolicy::class);
        Gate::policy(\App\Models\Lotofacil\Concurso::class, \App\Policies\Lotofacil\ConcursoPolicy::class);
        Gate::policy(\App\Models\Lotofacil\LotofacilFechamento::class, \App\Policies\Lotofacil\LotofacilFechamentoPolicy::class);
        Gate::policy(\App\Models\Lotofacil\EstrategiaFechamento::class, \App\Policies\Lotofacil\EstrategiaFechamentoPolicy::class);
        Gate::policy(\App\Models\Lotofacil\Combinacao18::class, \App\Policies\Lotofacil\Combinacao18Policy::class);

        // Registro de Policies dos Recursos Mega-Sena
        Gate::policy(\App\Models\Megasena\Bolao::class, \App\Policies\Megasena\BolaoPolicy::class);
        Gate::policy(\App\Models\Megasena\Concurso::class, \App\Policies\Megasena\ConcursoPolicy::class);
        Gate::policy(\App\Models\Megasena\MegasenaFechamento::class, \App\Policies\Megasena\MegasenaFechamentoPolicy::class);

        // Registro de Policy de Roles (Shield)
        Gate::policy(\Spatie\Permission\Models\Role::class, \App\Policies\RolePolicy::class);
    }
}
