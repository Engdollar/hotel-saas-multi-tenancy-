<?php

namespace App\Policies;

use App\Policies\Concerns\EnforcesCompanyBoundary;
use App\Models\User;

class UserPolicy
{
    use EnforcesCompanyBoundary;

    public function viewAny(User $user): bool
    {
        return $user->can('read-user');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('show-user') && $this->inSameCompany($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can('create-user');
    }

    public function update(User $user, User $model): bool
    {
        return ($user->can('update-user') || $user->can('edit-user'))
            && $this->inSameCompany($user, $model);
    }

    public function delete(User $user, User $model): bool
    {
        return $user->can('delete-user') && $this->inSameCompany($user, $model);
    }
}