<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CompanyOnboardingService
{
    public function __construct(
        protected PermissionGeneratorService $permissionGeneratorService,
        protected TenancyDomainService $tenancyDomainService,
    )
    {
    }

    public function registerCompanyWithAdmin(array $payload): array
    {
        $company = Company::query()->create([
            'name' => $payload['company_name'],
            'domain' => $this->resolveDomain($payload),
            'status' => Company::STATUS_PENDING,
        ]);

        $permissions = $this->permissionGeneratorService->generate(null);

        $role = Role::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Admin',
            'guard_name' => 'web',
            'is_locked' => true,
        ]);
        $role->syncPermissions($permissions);

        $user = User::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => $payload['name'],
            'email' => $payload['email'],
            'password' => Hash::make($payload['password']),
        ]);
        $user->assignRole($role);

        $this->seedCompanySettings($company, $payload);

        return compact('company', 'role', 'user');
    }

    protected function seedCompanySettings(Company $company, array $payload): void
    {
        $defaults = [
            'project_title' => $company->name,
            'theme_preset' => SettingsService::DEFAULT_THEME_PRESET,
            'theme_mode' => 'dark',
            'auth_login_visual_mode' => 'default',
            'auth_register_visual_mode' => 'default',
        ];

        foreach ($defaults as $key => $value) {
            Setting::withoutGlobalScopes()->updateOrCreate(
                ['company_id' => $company->id, 'key' => $key],
                ['value' => $value],
            );
        }
    }

    protected function resolveDomain(array $payload): ?string
    {
        $customDomain = $this->tenancyDomainService->qualifyDomain($payload['custom_domain'] ?? null);

        if ($customDomain !== '' && config('tenancy.allow_custom_domains', true)) {
            return Str::lower($customDomain);
        }

        return $this->tenancyDomainService->qualifyDomain($payload['subdomain'] ?? null);
    }
}
