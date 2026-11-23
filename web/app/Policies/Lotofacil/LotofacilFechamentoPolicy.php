<?php

declare(strict_types=1);

namespace App\Policies\Lotofacil;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Lotofacil\LotofacilFechamento;
use Illuminate\Auth\Access\HandlesAuthorization;

class LotofacilFechamentoPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return true;
    }

    public function view(AuthUser $authUser, LotofacilFechamento $lotofacilFechamento): bool
    {
        return true;
    }

    public function create(AuthUser $authUser): bool
    {
        return true;
    }

    public function update(AuthUser $authUser, LotofacilFechamento $lotofacilFechamento): bool
    {
        return true;
    }

    public function delete(AuthUser $authUser, LotofacilFechamento $lotofacilFechamento): bool
    {
        return true;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return true;
    }

    public function restore(AuthUser $authUser, LotofacilFechamento $lotofacilFechamento): bool
    {
        return true;
    }

    public function forceDelete(AuthUser $authUser, LotofacilFechamento $lotofacilFechamento): bool
    {
        return true;
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return true;
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return true;
    }

    public function replicate(AuthUser $authUser, LotofacilFechamento $lotofacilFechamento): bool
    {
        return true;
    }

    public function reorder(AuthUser $authUser): bool
    {
        return true;
    }
}
