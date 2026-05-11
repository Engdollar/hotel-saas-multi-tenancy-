<?php

namespace App\Support\Tenancy;

use App\Models\User;

class CurrentCompanyContext
{
    protected ?int $companyId = null;

    protected bool $bypass = true;

    public function initializeFromUser(?User $user, ?int $selectedCompanyId = null): void
    {
        if (! $user) {
            if ($selectedCompanyId !== null) {
                $this->bypass = false;
                $this->companyId = $selectedCompanyId;

                return;
            }

            $this->bypass = true;
            $this->companyId = null;

            return;
        }

        if ($user->isSuperAdmin()) {
            if ($selectedCompanyId !== null) {
                $this->bypass = false;
                $this->companyId = $selectedCompanyId;

                return;
            }

            $this->bypass = true;
            $this->companyId = null;

            return;
        }

        $this->bypass = false;
        $this->companyId = $user->company_id;
    }

    public function set(?int $companyId, bool $bypass = false): void
    {
        $this->companyId = $companyId;
        $this->bypass = $bypass;
    }

    public function id(): ?int
    {
        return $this->companyId;
    }

    public function bypassesTenancy(): bool
    {
        return $this->bypass;
    }
}
