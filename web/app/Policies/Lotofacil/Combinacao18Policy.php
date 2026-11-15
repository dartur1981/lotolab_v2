<?php

declare(strict_types=1);

namespace App\Policies\Lotofacil;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Lotofacil\Combinacao18;
use Illuminate\Auth\Access\HandlesAuthorization;

class Combinacao18Policy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Combinacao18');
    }

    public function view(AuthUser $authUser, Combinacao18 $combinacao18): bool
    {
        return $authUser->can('View:Combinacao18');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Combinacao18');
    }

    public function update(AuthUser $authUser, Combinacao18 $combinacao18): bool
    {
        return $authUser->can('Update:Combinacao18');
    }

    public function delete(AuthUser $authUser, Combinacao18 $combinacao18): bool
    {
        return $authUser->can('Delete:Combinacao18');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Combinacao18');
    }

    public function restore(AuthUser $authUser, Combinacao18 $combinacao18): bool
    {
        return $authUser->can('Restore:Combinacao18');
    }

    public function forceDelete(AuthUser $authUser, Combinacao18 $combinacao18): bool
    {
        return $authUser->can('ForceDelete:Combinacao18');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Combinacao18');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Combinacao18');
    }

    public function replicate(AuthUser $authUser, Combinacao18 $combinacao18): bool
    {
        return $authUser->can('Replicate:Combinacao18');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Combinacao18');
    }

}