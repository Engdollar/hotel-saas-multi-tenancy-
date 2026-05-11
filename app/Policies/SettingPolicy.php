<?php

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;
use App\Policies\Concerns\EnforcesCompanyBoundary;

class SettingPolicy
{
    use EnforcesCompanyBoundary;

    public function manage(User $user): bool
    {
        return $user->can('update-setting') || $user->can('edit-setting');
    }

    public function view(User $user, Setting $setting): bool
    {
        return $user->can('read-setting') && $this->inSameCompany($user, $setting);
    }

    public function viewAny(User $user): bool
    {
        return $user->can('read-setting');
    }

    public function update(User $user, Setting $setting): bool
    {
        return ($user->can('update-setting') || $user->can('edit-setting'))
            && $this->inSameCompany($user, $setting);
    }

    public function delete(User $user, Setting $setting): bool
    {
        return $user->can('delete-setting') && $this->inSameCompany($user, $setting);
    }
}