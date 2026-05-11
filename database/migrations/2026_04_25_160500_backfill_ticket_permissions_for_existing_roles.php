<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');

        $permissionsTable = $tableNames['permissions'] ?? 'permissions';
        $rolesTable = $tableNames['roles'] ?? 'roles';
        $roleHasPermissionsTable = $tableNames['role_has_permissions'] ?? 'role_has_permissions';

        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        $now = now();
        $guardName = 'web';
        $abilities = ['create', 'read', 'show', 'update', 'edit', 'delete', 'find'];

        foreach ($abilities as $ability) {
            DB::table($permissionsTable)->updateOrInsert(
                [
                    'company_id' => null,
                    'name' => "{$ability}-ticket",
                    'guard_name' => $guardName,
                ],
                [
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        $ticketPermissionIds = DB::table($permissionsTable)
            ->where('company_id', null)
            ->where('guard_name', $guardName)
            ->whereIn('name', array_map(fn (string $ability) => "{$ability}-ticket", $abilities))
            ->pluck('id');

        if ($ticketPermissionIds->isEmpty()) {
            return;
        }

        $roleIds = DB::table($rolesTable)
            ->where('guard_name', $guardName)
            ->whereIn('name', ['Admin', 'Super Admin'])
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($ticketPermissionIds as $permissionId) {
                DB::table($roleHasPermissionsTable)->updateOrInsert(
                    [
                        $pivotRole => $roleId,
                        $pivotPermission => $permissionId,
                    ],
                    []
                );
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');

        $permissionsTable = $tableNames['permissions'] ?? 'permissions';
        $roleHasPermissionsTable = $tableNames['role_has_permissions'] ?? 'role_has_permissions';

        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        $ticketPermissionIds = DB::table($permissionsTable)
            ->where('company_id', null)
            ->where('guard_name', 'web')
            ->whereIn('name', [
                'create-ticket',
                'read-ticket',
                'show-ticket',
                'update-ticket',
                'edit-ticket',
                'delete-ticket',
                'find-ticket',
            ])
            ->pluck('id');

        if ($ticketPermissionIds->isNotEmpty()) {
            DB::table($roleHasPermissionsTable)
                ->whereIn($pivotPermission, $ticketPermissionIds)
                ->delete();

            DB::table($permissionsTable)
                ->whereIn('id', $ticketPermissionIds)
                ->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
