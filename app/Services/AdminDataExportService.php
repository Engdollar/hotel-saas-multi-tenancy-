<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AdminDataExportService
{
    public function users(?string $search = null): array
    {
        $rows = $this->usersQuery($search)
            ->get()
            ->map(fn (User $user) => [
                $user->name,
                $user->email,
                $user->roles->pluck('name')->join(', '),
                $user->created_at?->format('Y-m-d H:i'),
            ]);

        return [
            'title' => 'Users Directory',
            'headings' => ['Name', 'Email', 'Roles', 'Created At'],
            'rows' => $rows,
            'filename' => 'users-directory',
        ];
    }

    public function roles(?string $search = null): array
    {
        $rows = $this->rolesQuery($search)
            ->get()
            ->map(fn (Role $role) => [
                $role->name,
                (string) $role->permissions_count,
                (string) $role->users_count,
                $role->created_at?->format('Y-m-d H:i'),
            ]);

        return [
            'title' => 'Roles Catalog',
            'headings' => ['Role', 'Permissions', 'Users', 'Created At'],
            'rows' => $rows,
            'filename' => 'roles-catalog',
        ];
    }

    public function permissions(?string $search = null): array
    {
        $rows = $this->permissionsQuery($search)
            ->get()
            ->map(fn (Permission $permission) => [
                $permission->name,
                (string) $permission->roles_count,
                $permission->created_at?->format('Y-m-d H:i'),
            ]);

        return [
            'title' => 'Permissions Inventory',
            'headings' => ['Permission', 'Roles', 'Created At'],
            'rows' => $rows,
            'filename' => 'permissions-inventory',
        ];
    }

    protected function usersQuery(?string $search = null): Builder
    {
        return User::query()
            ->with('roles')
            ->when($search, function (Builder $query, string $search) {
                $query->where(function (Builder $nested) use ($search) {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('roles', fn (Builder $roleQuery) => $roleQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest();
    }

    protected function rolesQuery(?string $search = null): Builder
    {
        return Role::query()
            ->withCount(['permissions', 'users'])
            ->when($search, fn (Builder $query, string $search) => $query->where('name', 'like', "%{$search}%"))
            ->latest();
    }

    protected function permissionsQuery(?string $search = null): Builder
    {
        return Permission::query()
            ->withCount('roles')
            ->when($search, fn (Builder $query, string $search) => $query->where('name', 'like', "%{$search}%"))
            ->latest();
    }
}