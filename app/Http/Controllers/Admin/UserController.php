<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\AdminNotificationService;
use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function __construct(
        protected AdminNotificationService $notificationService,
        protected CurrentCompanyContext $companyContext,
    )
    {
    }

    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        return view('admin.users.index');
    }

    public function data(): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $query = User::query()->with('roles');

        return DataTables::eloquent($query)
            ->editColumn('name', function (User $user) {
                return view('admin.users.partials.name-cell', compact('user'))->render();
            })
            ->addColumn('roles_label', function (User $user) {
                return view('admin.users.partials.roles-cell', compact('user'))->render();
            })
            ->editColumn('created_at', fn (User $user) => $user->created_at?->format('M d, Y'))
            ->addColumn('actions', function (User $user) {
                return view('admin.users.partials.actions', compact('user'))->render();
            })
            ->filterColumn('roles_label', function ($query, $keyword) {
                $query->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', 'like', "%{$keyword}%"));
            })
            ->rawColumns(['name', 'roles_label', 'actions'])
            ->toJson();
    }

    public function create(Request $request): View
    {
        $this->authorize('create', User::class);

        $viewData = [
            'roles' => $this->assignableRoles(),
            'lockedRoleNames' => [],
        ];

        if ($request->boolean('modal')) {
            return view('admin.users.partials.modal-form', $viewData);
        }

        return view('admin.users.create', $viewData);
    }

    public function store(StoreUserRequest $request): JsonResponse|RedirectResponse
    {
        $this->authorize('create', User::class);

        $data = $request->validated();
        $roles = $data['roles'] ?? [];
        unset($data['roles'], $data['profile_image']);

        if ($this->companyContext->id() === null && auth()->user()?->isSuperAdmin()) {
            return back()->with('error', 'Select a company from the top switcher before creating tenant users.');
        }

        $data['company_id'] = $this->companyContext->id();

        $data['password'] = Hash::make($data['password']);

        if ($request->hasFile('profile_image')) {
            $data['profile_image_path'] = $request->file('profile_image')->store('profiles', 'public');
        }

        $user = User::create($data);
    $this->syncUserRoles($user, $roles);

        $this->notificationService->send('User created', "{$user->name} was added to the platform.", route('admin.users.show', $user));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'User created successfully.',
            ]);
        }

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function show(User $user): View
    {
        $this->authorize('view', $user);

        return view('admin.users.show', [
            'user' => $user->load('roles'),
            'activities' => Activity::query()
                ->where('subject_type', User::class)
                ->where('subject_id', $user->id)
                ->latest()
                ->take(6)
                ->get(),
        ]);
    }

    public function edit(Request $request, User $user): View
    {
        $this->authorize('update', $user);

        $user->load('roles');

        $viewData = [
            'user' => $user,
            'roles' => $this->assignableRoles(),
            'lockedRoleNames' => $user->roles
                ->where('is_locked', true)
                ->pluck('name')
                ->values()
                ->all(),
        ];

        if ($request->boolean('modal')) {
            return view('admin.users.partials.modal-form', $viewData);
        }

        return view('admin.users.edit', $viewData);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $request->validated();
        $roles = $data['roles'] ?? [];
        unset($data['roles'], $data['profile_image']);

        if (! auth()->user()?->isSuperAdmin()) {
            $data['company_id'] = auth()->user()->company_id;
        }

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        if ($request->hasFile('profile_image')) {
            if ($user->profile_image_path) {
                Storage::disk('public')->delete($user->profile_image_path);
            }

            $data['profile_image_path'] = $request->file('profile_image')->store('profiles', 'public');
        }

        $user->update($data);
    $this->syncUserRoles($user, $roles);

        $this->notificationService->send('User updated', "{$user->name} was updated.", route('admin.users.show', $user));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'User updated successfully.',
            ]);
        }

        return redirect()->route('admin.users.edit', $user)->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if ($user->is(auth()->user())) {
            return back()->with('error', 'You cannot delete the currently authenticated user.');
        }

        if ($user->profile_image_path) {
            Storage::disk('public')->delete($user->profile_image_path);
        }

        $name = $user->name;
        $user->delete();

        $this->notificationService->send('User removed', "{$name} was removed from the platform.");

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }

    protected function assignableRoles()
    {
        return Role::query()
            ->whereNotNull('company_id')
            ->where('is_locked', false)
            ->withCount('permissions')
            ->orderBy('name')
            ->get();
    }

    protected function syncUserRoles(User $user, array $roles): void
    {
        $lockedRoleNames = $user->roles()
            ->where('is_locked', true)
            ->pluck('name')
            ->all();

        $user->syncRoles(array_values(array_unique([...$lockedRoleNames, ...$roles])));
    }
}