<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePermissionRequest;
use App\Http\Requests\UpdatePermissionRequest;
use App\Models\Permission;
use App\Services\AdminNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class PermissionController extends Controller
{
    public function __construct(protected AdminNotificationService $notificationService)
    {
    }

    public function index(): View
    {
        $this->authorize('viewAny', Permission::class);

        return view('admin.permissions.index');
    }

    public function data(): JsonResponse
    {
        $this->authorize('viewAny', Permission::class);

        $query = Permission::query()->withCount('roles');

        return DataTables::eloquent($query)
            ->editColumn('name', fn (Permission $permission) => view('admin.permissions.partials.name-cell', compact('permission'))->render())
            ->addColumn('roles_count', fn (Permission $permission) => $permission->roles_count)
            ->editColumn('created_at', fn (Permission $permission) => $permission->created_at?->format('M d, Y'))
            ->addColumn('actions', fn (Permission $permission) => view('admin.permissions.partials.actions', compact('permission'))->render())
            ->rawColumns(['name', 'actions'])
            ->toJson();
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Permission::class);

        if ($request->boolean('modal')) {
            return view('admin.permissions.partials.modal-form');
        }

        return view('admin.permissions.create');
    }

    public function store(StorePermissionRequest $request): JsonResponse|RedirectResponse
    {
        $this->authorize('create', Permission::class);

        $permission = Permission::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);

        $this->notificationService->send('Permission created', "{$permission->name} was added.", route('admin.permissions.show', $permission));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Permission created successfully.',
            ]);
        }

        return redirect()->route('admin.permissions.index')->with('success', 'Permission created successfully.');
    }

    public function show(Permission $permission): View
    {
        $this->authorize('view', $permission);

        return view('admin.permissions.show', [
            'permission' => $permission->load('roles'),
        ]);
    }

    public function edit(Request $request, Permission $permission): View
    {
        $this->authorize('update', $permission);

        if ($request->boolean('modal')) {
            return view('admin.permissions.partials.modal-form', [
                'permission' => $permission,
            ]);
        }

        return view('admin.permissions.edit', [
            'permission' => $permission,
        ]);
    }

    public function update(UpdatePermissionRequest $request, Permission $permission): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $permission);

        $permission->update(['name' => $request->validated('name')]);

        $this->notificationService->send('Permission updated', "{$permission->name} was updated.", route('admin.permissions.show', $permission));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Permission updated successfully.',
            ]);
        }

        return redirect()->route('admin.permissions.edit', $permission)->with('success', 'Permission updated successfully.');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        $this->authorize('delete', $permission);

        $name = $permission->name;
        $permission->delete();

        $this->notificationService->send('Permission deleted', "{$name} was removed from the permission catalog.");

        return redirect()->route('admin.permissions.index')->with('success', 'Permission deleted successfully.');
    }
}