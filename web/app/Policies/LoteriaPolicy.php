<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Loteria;
use Illuminate\Auth\Access\HandlesAuthorization;

class LoteriaPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Loteria');
    }

    public function view(AuthUser $authUser, Loteria $loteria): bool
    {
        return $authUser->can('View:Loteria');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Loteria');
    }

    public function update(AuthUser $authUser, Loteria $loteria): bool
    {
        return $authUser->can('Update:Loteria');
    }

    public function delete(AuthUser $authUser, Loteria $loteria): bool
    {
        return $authUser->can('Delete:Loteria');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Loteria');
    }

    public function restore(AuthUser $authUser, Loteria $loteria): bool
    {
        return $authUser->can('Restore:Loteria');
    }

    public function forceDelete(AuthUser $authUser, Loteria $loteria): bool
    {
        return $authUser->can('ForceDelete:Loteria');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Loteria');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Loteria');
    }

    public function replicate(AuthUser $authUser, Loteria $loteria): bool
    {
        return $authUser->can('Replicate:Loteria');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Loteria');
    }

}