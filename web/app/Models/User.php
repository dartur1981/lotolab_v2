<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'last_loteria_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements HasTenants, FilamentUser, HasDefaultTenant
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function loterias()
    {
        return $this->belongsToMany(Loteria::class);
    }

    public function boloes_lotofacil()
    {
        return $this->belongsToMany(\App\Models\Lotofacil\Bolao::class, 'lotofacil_bolao_user', 'user_id', 'lotofacil_bolao_id');
    }

    public function megasenaBoloes()
    {
        return $this->belongsToMany(\App\Models\Megasena\Bolao::class, 'megasena_bolao_user', 'user_id', 'megasena_bolao_id');
    }

    public function quinaBoloes()
    {
        return $this->belongsToMany(\App\Models\Quina\Bolao::class, 'quina_bolao_user', 'user_id', 'quina_bolao_id');
    }

    public function lastLoteria()
    {
        return $this->belongsTo(Loteria::class, 'last_loteria_id');
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        $tenantId = $this->last_loteria_id
            ?? session('filament_last_tenant_id')
            ?? request()->cookie('filament_last_tenant_id');

        if ($tenantId) {
            $tenant = $this->loterias()->where('loterias.id', $tenantId)->first();
            if ($tenant) {
                return $tenant;
            }
        }

        return $this->loterias()->first();
    }

    public function getTenants(Panel $panel): array|Collection
    {
        return $this->loterias;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->loterias()->whereKey($tenant)->exists();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->hasRole(config('filament-shield.super_admin.name', 'super_admin'))
                || $this->can('access_admin_panel')
                || $this->email === 'admin@admin.com';
        }

        return true;
    }
}
