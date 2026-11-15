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
        return $authUser->can('ViewAny:LotofacilFechamento');
    }

    public function view(AuthUser $authUser, LotofacilFechamento $lotofacilFechamento): bool
    {
        return $authUser->can('View:LotofacilFechamento');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:LotofacilFechamento');
    }

    public function update(AuthUser $authUser, LotofacilFechamento $lotofacilFechamento): bool
    {
        return $authUser->can('Update:LotofacilFechamento');
    }

    public function delete(AuthUser $authUser, LotofacilFechamento $lotofacilFechamento): bool
    {
        return $authUser->can('Delete:LotofacilFechamento');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:LotofacilFechamento');
    }

    public function restore(AuthUser $authUser, LotofacilFechamento $lotofacilFechamento): bool
    {
        return $authUser->can('Restore:LotofacilFechamento');
    }

    public function forceDelete(AuthUser $authUser, LotofacilFechamento $lotofacilFechamento): bool
    {
        return $authUser->can('ForceDelete:LotofacilFechamento');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:LotofacilFechamento');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:LotofacilFechamento');
    }

    public function replicate(AuthUser $authUser, LotofacilFechamento $lotofacilFechamento): bool
    {
        return $authUser->can('Replicate:LotofacilFechamento');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:LotofacilFechamento');
    }

}