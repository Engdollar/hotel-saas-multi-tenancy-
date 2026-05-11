<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Services\PermissionGeneratorService;
use Illuminate\Database\Seeder;

class SystemSetupSeeder extends Seeder
{
    public function run(): void
    {
        /** @var PermissionGeneratorService $permissionGeneratorService */
        $permissionGeneratorService = app(PermissionGeneratorService::class);

        $permissionGeneratorService->setupSuperAdmin();

        $company = Company::query()->firstOrCreate(
            ['name' => env('DEFAULT_COMPANY_NAME', 'Default Company')],
            [
                'status' => 'active',
                'domain' => env('DEFAULT_COMPANY_DOMAIN'),
            ],
        );

        $permissionGeneratorService->setupDefaultCompany($company);
    }
}
