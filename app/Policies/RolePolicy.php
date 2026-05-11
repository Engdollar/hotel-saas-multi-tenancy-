<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use App\Policies\Concerns\EnforcesCompanyBoundary;

class RolePolicy
{
    use EnforcesCompanyBoundary;

    public function viewAny(User $user): bool
    {
        return $user->can('read-role');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('show-role') && $this->inSameCompany($user, $role);
    }

    public function create(User $user): bool
    {
        return $user->can('create-role');
    }

    public function update(User $user, Role $role): bool
    {
        return ($user->can('update-role') || $user->can('edit-role'))
            && ! $role->is_locked
            && $this->inSameCompany($user, $role);
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can('delete-role')
            && $role->name !== 'Super Admin'
            && ! $role->is_locked
            && $this->inSameCompany($user, $role);
    }
}