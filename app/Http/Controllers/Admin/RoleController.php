<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Role;
use App\Services\AdminNotificationService;
use App\Services\PermissionGeneratorService;
use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class RoleController extends Controller
{
    public function __construct(
        protected PermissionGeneratorService $permissionGeneratorService,
        protected AdminNotificationService $notificationService,
        protected CurrentCompanyContext $companyContext,
    ) {
    }

    public function index(): View
    {
        $this->authorize('viewAny', Role::class);

        return view('admin.roles.index');
    }

    public function data(): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        $query = Role::query()->whereNotNull('company_id')->withCount(['permissions', 'users']);

        return DataTables::eloquent($query)
            ->editColumn('name', fn (Role $role) => view('admin.roles.partials.name-cell', compact('role'))->render())
            ->addColumn('permissions_count', fn (Role $role) => $role->permissions_count)
            ->addColumn('users_count', fn (Role $role) => $role->users_count)
            ->editColumn('created_at', fn (Role $role) => $role->created_at?->format('M d, Y'))
            ->addColumn('actions', fn (Role $role) => view('admin.roles.partials.actions', compact('role'))->render())
            ->rawColumns(['name', 'actions'])
            ->toJson();
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Role::class);

        $viewData = [
            'permissionGroups' => $this->permissionGeneratorService->groupedPermissions(),
        ];

        if ($request->boolean('modal')) {
            return view('admin.roles.partials.modal-form', $viewData);
        }

        return view('admin.roles.create', $viewData);
    }

    public function store(StoreRoleRequest $request): JsonResponse|RedirectResponse
    {
        $this->authorize('create', Role::class);

        if ($this->companyContext->id() === null) {
            return back()->with('error', 'Select a company before creating roles.');
        }

        $role = Role::create([
            'company_id' => $this->companyContext->id(),
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($request->validated('permissions', []));

        $this->notificationService->send('Role created', "{$role->name} is now available for assignment.", route('admin.roles.show', $role));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Role created successfully.',
            ]);
        }

        return redirect()->route('admin.roles.index')->with('success', 'Role created successfully.');
    }

    public function show(Role $role): View
    {
        $this->authorize('view', $role);

        return view('admin.roles.show', [
            'role' => $role->load(['permissions', 'users']),
        ]);
    }

    public function edit(Request $request, Role $role): View
    {
        $this->authorize('update', $role);

        $viewData = [
            'role' => $role->load('permissions'),
            'permissionGroups' => $this->permissionGeneratorService->groupedPermissions(),
        ];

        if ($request->boolean('modal')) {
            return view('admin.roles.partials.modal-form', $viewData);
        }

        return view('admin.roles.edit', $viewData);
    }

    public function update(UpdateRoleRequest $request, Role $role): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $role);

        $role->update(['name' => $request->validated('name')]);
        $role->syncPermissions($request->validated('permissions', []));

        $this->notificationService->send('Role updated', "{$role->name} permissions were refreshed.", route('admin.roles.show', $role));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Role updated successfully.',
            ]);
        }

        return redirect()->route('admin.roles.edit', $role)->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        $name = $role->name;
        $role->delete();

        $this->notificationService->send('Role deleted', "{$name} was removed from the RBAC catalog.");

        return redirect()->route('admin.roles.index')->with('success', 'Role deleted successfully.');
    }
}