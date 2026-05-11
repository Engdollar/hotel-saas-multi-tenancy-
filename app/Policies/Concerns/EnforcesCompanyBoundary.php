<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait EnforcesCompanyBoundary
{
    protected function inSameCompany(User $user, mixed $model, bool $allowGlobalModel = false): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $modelCompanyId = data_get($model, 'company_id');

        if ($modelCompanyId === null) {
            return $allowGlobalModel;
        }

        if ($user->company_id === null) {
            return false;
        }

        return (int) $user->company_id === (int) $modelCompanyId;
    }
}
