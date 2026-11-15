<?php

declare(strict_types=1);

namespace App\Policies\Megasena;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Megasena\MegasenaFechamento;
use Illuminate\Auth\Access\HandlesAuthorization;

class MegasenaFechamentoPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MegasenaFechamento');
    }

    public function view(AuthUser $authUser, MegasenaFechamento $megasenaFechamento): bool
    {
        return $authUser->can('View:MegasenaFechamento');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MegasenaFechamento');
    }

    public function update(AuthUser $authUser, MegasenaFechamento $megasenaFechamento): bool
    {
        return $authUser->can('Update:MegasenaFechamento');
    }

    public function delete(AuthUser $authUser, MegasenaFechamento $megasenaFechamento): bool
    {
        return $authUser->can('Delete:MegasenaFechamento');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MegasenaFechamento');
    }

    public function restore(AuthUser $authUser, MegasenaFechamento $megasenaFechamento): bool
    {
        return $authUser->can('Restore:MegasenaFechamento');
    }

    public function forceDelete(AuthUser $authUser, MegasenaFechamento $megasenaFechamento): bool
    {
        return $authUser->can('ForceDelete:MegasenaFechamento');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MegasenaFechamento');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MegasenaFechamento');
    }

    public function replicate(AuthUser $authUser, MegasenaFechamento $megasenaFechamento): bool
    {
        return $authUser->can('Replicate:MegasenaFechamento');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MegasenaFechamento');
    }

}