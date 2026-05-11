<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\PermissionGeneratorService;
use Illuminate\Console\Command;

class SystemSetupCommand extends Command
{
    protected $signature = 'system:setup {--refresh-passwords : Re-apply admin passwords from environment values}';

    protected $description = 'Create super admin, default company, and seed roles with permissions.';

    public function handle(PermissionGeneratorService $permissionGeneratorService): int
    {
        $refreshPasswords = (bool) $this->option('refresh-passwords');

        $superAdmin = $permissionGeneratorService->setupSuperAdmin($refreshPasswords);

        $company = Company::query()->firstOrCreate(
            ['name' => env('DEFAULT_COMPANY_NAME', 'Default Company')],
            [
                'status' => 'active',
                'domain' => env('DEFAULT_COMPANY_DOMAIN'),
            ],
        );

        $companySetup = $permissionGeneratorService->setupDefaultCompany($company, $refreshPasswords);

        $this->components->info("Generated {$superAdmin['permissions_count']} global permissions.");
        $this->components->info("Super Admin: {$superAdmin['user']->email}");
        if ($refreshPasswords) {
            $this->components->info('Admin passwords refreshed from environment values.');
        }
        $this->components->info("Default Company: {$company->name}");
        $this->components->info("Company Admin: {$companySetup['user']->email}");

        return self::SUCCESS;
    }
}
