<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class PermissionGeneratorService
{
    public const MODULES = [
        'dashboard',
        'report',
        'intelligence',
        'user',
        'role',
        'permission',
        'setting',
        'ticket',
        'property',
        'room-type',
        'room',
        'guest',
        'reservation',
        'housekeeping',
        'maintenance',
        'ledger',
        'journal',
        'invoice',
        'payment',
        'folio',
        'refund',
        'supplier',
        'supplier-bill',
        'supplier-payment',
        'branch',
        'subscription',
        'housekeeping-task',
        'maintenance-request',
        'preventive-maintenance-schedule',
        'bank-account',
        'bank-reconciliation',
        'pos-order',
        'pos-cashier-shift',
        'inventory-item',
        'purchase-order',
        'purchase-order-approval',
    ];

    public const ABILITIES = [
        'create',
        'read',
        'show',
        'update',
        'edit',
        'delete',
        'find',
    ];

    public function generate(?int $companyId = null): Collection
    {
        $permissions = collect();

        foreach (self::MODULES as $module) {
            foreach (self::ABILITIES as $ability) {
                $name = "{$ability}-{$module}";
                $permission = Permission::withoutGlobalScopes()->firstOrCreate(
                    [
                        'company_id' => $companyId,
                        'name' => $name,
                        'guard_name' => 'web',
                    ]
                );
                $permissions->push($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $permissions;
    }

    public function groupedPermissions(): Collection
    {
        return Permission::query()
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Permission $permission) => str($permission->name)->after('-')->headline()->toString());
    }

    public function setupSuperAdmin(bool $refreshCredentials = false): array
    {
        $permissions = $this->generate(null);

        $role = Role::withoutGlobalScopes()->firstOrCreate([
            'company_id' => null,
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ]);
        $role->forceFill(['is_locked' => true])->save();
        $role->syncPermissions($permissions);

        $user = User::withoutGlobalScopes()->firstOrCreate(
            ['email' => env('RBAC_ADMIN_EMAIL', 'admin@example.com')],
            [
                'name' => env('RBAC_ADMIN_NAME', 'Super Admin'),
                'company_id' => null,
                'password' => Hash::make(env('RBAC_ADMIN_PASSWORD', 'password')),
            ],
        );

        if ($refreshCredentials) {
            $user->forceFill([
                'name' => env('RBAC_ADMIN_NAME', 'Super Admin'),
                'password' => Hash::make(env('RBAC_ADMIN_PASSWORD', 'password')),
            ]);
        }

        $user->company_id = null;
        $user->save();
        $user->syncRoles([$role->name]);

        return [
            'role' => $role,
            'user' => $user,
            'permissions_count' => $permissions->count(),
        ];
    }

    public function setupDefaultCompany(Company $company, bool $refreshCredentials = false): array
    {
        $permissions = $this->generate(null);

        $adminRole = Role::withoutGlobalScopes()->firstOrCreate([
            'company_id' => $company->id,
            'name' => 'Admin',
            'guard_name' => 'web',
        ]);
        $adminRole->forceFill(['is_locked' => true])->save();

        $adminRole->syncPermissions($permissions);

        $companyAdmin = User::withoutGlobalScopes()->firstOrCreate(
            ['email' => env('DEFAULT_COMPANY_ADMIN_EMAIL', 'company-admin@example.com')],
            [
                'name' => env('DEFAULT_COMPANY_ADMIN_NAME', 'Company Admin'),
                'company_id' => $company->id,
                'password' => Hash::make(env('DEFAULT_COMPANY_ADMIN_PASSWORD', 'password')),
            ],
        );

        if ($refreshCredentials) {
            $companyAdmin->forceFill([
                'name' => env('DEFAULT_COMPANY_ADMIN_NAME', 'Company Admin'),
                'password' => Hash::make(env('DEFAULT_COMPANY_ADMIN_PASSWORD', 'password')),
            ]);
        }

        $companyAdmin->company_id = $company->id;
        $companyAdmin->save();
        $companyAdmin->syncRoles([$adminRole->name]);

        return [
            'role' => $adminRole,
            'user' => $companyAdmin,
            'permissions_count' => $permissions->count(),
        ];
    }
}