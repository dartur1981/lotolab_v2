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
        return true;
    }

    public function view(AuthUser $authUser, MegasenaFechamento $megasenaFechamento): bool
    {
        return true;
    }

    public function create(AuthUser $authUser): bool
    {
        return true;
    }

    public function update(AuthUser $authUser, MegasenaFechamento $megasenaFechamento): bool
    {
        return true;
    }

    public function delete(AuthUser $authUser, MegasenaFechamento $megasenaFechamento): bool
    {
        return true;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return true;
    }

    public function restore(AuthUser $authUser, MegasenaFechamento $megasenaFechamento): bool
    {
        return true;
    }

    public function forceDelete(AuthUser $authUser, MegasenaFechamento $megasenaFechamento): bool
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

    public function replicate(AuthUser $authUser, MegasenaFechamento $megasenaFechamento): bool
    {
        return true;
    }

    public function reorder(AuthUser $authUser): bool
    {
        return true;
    }
}
