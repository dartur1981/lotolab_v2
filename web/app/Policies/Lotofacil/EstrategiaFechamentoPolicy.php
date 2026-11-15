<?php

declare(strict_types=1);

namespace App\Policies\Lotofacil;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Lotofacil\EstrategiaFechamento;
use Illuminate\Auth\Access\HandlesAuthorization;

class EstrategiaFechamentoPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EstrategiaFechamento');
    }

    public function view(AuthUser $authUser, EstrategiaFechamento $estrategiaFechamento): bool
    {
        return $authUser->can('View:EstrategiaFechamento');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EstrategiaFechamento');
    }

    public function update(AuthUser $authUser, EstrategiaFechamento $estrategiaFechamento): bool
    {
        return $authUser->can('Update:EstrategiaFechamento');
    }

    public function delete(AuthUser $authUser, EstrategiaFechamento $estrategiaFechamento): bool
    {
        return $authUser->can('Delete:EstrategiaFechamento');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:EstrategiaFechamento');
    }

    public function restore(AuthUser $authUser, EstrategiaFechamento $estrategiaFechamento): bool
    {
        return $authUser->can('Restore:EstrategiaFechamento');
    }

    public function forceDelete(AuthUser $authUser, EstrategiaFechamento $estrategiaFechamento): bool
    {
        return $authUser->can('ForceDelete:EstrategiaFechamento');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:EstrategiaFechamento');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:EstrategiaFechamento');
    }

    public function replicate(AuthUser $authUser, EstrategiaFechamento $estrategiaFechamento): bool
    {
        return $authUser->can('Replicate:EstrategiaFechamento');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:EstrategiaFechamento');
    }

}