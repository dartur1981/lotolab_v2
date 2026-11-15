<?php

declare(strict_types=1);

namespace App\Policies\Megasena;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Megasena\Bolao;
use Illuminate\Auth\Access\HandlesAuthorization;

class BolaoPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Bolao');
    }

    public function view(AuthUser $authUser, Bolao $bolao): bool
    {
        return $authUser->can('View:Bolao');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Bolao');
    }

    public function update(AuthUser $authUser, Bolao $bolao): bool
    {
        return $authUser->can('Update:Bolao');
    }

    public function delete(AuthUser $authUser, Bolao $bolao): bool
    {
        return $authUser->can('Delete:Bolao');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Bolao');
    }

    public function restore(AuthUser $authUser, Bolao $bolao): bool
    {
        return $authUser->can('Restore:Bolao');
    }

    public function forceDelete(AuthUser $authUser, Bolao $bolao): bool
    {
        return $authUser->can('ForceDelete:Bolao');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Bolao');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Bolao');
    }

    public function replicate(AuthUser $authUser, Bolao $bolao): bool
    {
        return $authUser->can('Replicate:Bolao');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Bolao');
    }

}