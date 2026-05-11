<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\User;
use App\Policies\Concerns\EnforcesCompanyBoundary;

class PermissionPolicy
{
    use EnforcesCompanyBoundary;

    public function viewAny(User $user): bool
    {
        return $user->can('read-permission');
    }

    public function view(User $user, Permission $permission): bool
    {
        return $user->can('show-permission') && $this->inSameCompany($user, $permission);
    }

    public function create(User $user): bool
    {
        return $user->can('create-permission');
    }

    public function update(User $user, Permission $permission): bool
    {
        return ($user->can('update-permission') || $user->can('edit-permission'))
            && $this->inSameCompany($user, $permission);
    }

    public function delete(User $user, Permission $permission): bool
    {
        return $user->can('delete-permission') && $this->inSameCompany($user, $permission);
    }
}