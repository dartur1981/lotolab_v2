<?php

declare(strict_types=1);

namespace App\Policies\Lotofacil;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Lotofacil\Concurso;
use Illuminate\Auth\Access\HandlesAuthorization;

class ConcursoPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Concurso');
    }

    public function view(AuthUser $authUser, Concurso $concurso): bool
    {
        return $authUser->can('View:Concurso');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Concurso');
    }

    public function update(AuthUser $authUser, Concurso $concurso): bool
    {
        return $authUser->can('Update:Concurso');
    }

    public function delete(AuthUser $authUser, Concurso $concurso): bool
    {
        return $authUser->can('Delete:Concurso');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Concurso');
    }

    public function restore(AuthUser $authUser, Concurso $concurso): bool
    {
        return $authUser->can('Restore:Concurso');
    }

    public function forceDelete(AuthUser $authUser, Concurso $concurso): bool
    {
        return $authUser->can('ForceDelete:Concurso');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Concurso');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Concurso');
    }

    public function replicate(AuthUser $authUser, Concurso $concurso): bool
    {
        return $authUser->can('Replicate:Concurso');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Concurso');
    }

}