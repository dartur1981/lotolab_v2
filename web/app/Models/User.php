<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements HasTenants
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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

    public function getTenants(Panel $panel): array|Collection
    {
        return $this->loterias;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->loterias()->whereKey($tenant)->exists();
    }
}
