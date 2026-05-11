<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $query = trim((string) $request->string('query'));
        [$groups, $restrictedDomains] = $this->searchGroups($query);
        $resultCount = collect($groups)->sum(fn (array $group) => count($group['items']));

        return view('admin.search.index', [
            'query' => $query,
            'resultCount' => $resultCount,
            'groups' => $groups,
            'restrictedDomains' => $restrictedDomains,
        ]);
    }

    protected function searchGroups(string $query): array
    {
        if ($query === '') {
            return [[], []];
        }

        $term = '%'.$query.'%';
        $user = auth()->user();
        $groups = [];
        $requestedDomains = $this->requestedDomains($query);
        $restrictedDomains = $requestedDomains
            ->reject(fn (string $domain) => $this->canSearchDomain($user, $domain))
            ->values();
        $allowedDomains = $requestedDomains
            ->reject(fn (string $domain) => $restrictedDomains->contains($domain))
            ->values();

        if ($requestedDomains->isNotEmpty() && $allowedDomains->isEmpty()) {
            return [[], $this->domainLabels($restrictedDomains)->all()];
        }

        if ($this->shouldSearchDomain('users', $allowedDomains) && $user->can('read-user')) {
            $items = User::query()
                ->where(function ($builder) use ($term) {
                    $builder->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                })
                ->limit(6)
                ->get()
                ->filter(fn (User $record) => $user->can('viewAny', User::class))
                ->map(fn (User $record) => [
                    'title' => $record->name,
                    'description' => $record->email,
                    'meta' => 'User',
                    'url' => $user->can('view', $record) ? route('admin.users.show', $record) : route('admin.users.index'),
                ]);

            if ($items->isNotEmpty()) {
                $groups[] = [
                    'title' => 'Users',
                    'icon' => 'user',
                    'items' => $items->all(),
                ];
            }
        }

        if ($this->shouldSearchDomain('roles', $allowedDomains) && $user->can('read-role')) {
            $items = Role::query()
                ->where('name', 'like', $term)
                ->limit(6)
                ->get()
                ->filter(fn (Role $record) => $user->can('viewAny', Role::class))
                ->map(fn (Role $record) => [
                    'title' => $record->name,
                    'description' => $record->permissions()->count().' permissions',
                    'meta' => 'Role',
                    'url' => $user->can('view', $record) ? route('admin.roles.show', $record) : route('admin.roles.index'),
                ]);

            if ($items->isNotEmpty()) {
                $groups[] = [
                    'title' => 'Roles',
                    'icon' => 'check-square',
                    'items' => $items->all(),
                ];
            }
        }

        if ($this->shouldSearchDomain('permissions', $allowedDomains) && $user->can('read-permission')) {
            $items = Permission::query()
                ->where('name', 'like', $term)
                ->limit(6)
                ->get()
                ->filter(fn (Permission $record) => $user->can('viewAny', Permission::class))
                ->map(fn (Permission $record) => [
                    'title' => $record->name,
                    'description' => $record->roles()->count().' roles use this permission',
                    'meta' => 'Permission',
                    'url' => $user->can('view', $record) ? route('admin.permissions.show', $record) : route('admin.permissions.index'),
                ]);

            if ($items->isNotEmpty()) {
                $groups[] = [
                    'title' => 'Permissions',
                    'icon' => 'settings',
                    'items' => $items->all(),
                ];
            }
        }

        if ($this->shouldSearchDomain('activity', $allowedDomains) && $user->can('read-dashboard')) {
            $items = Activity::query()
                ->with('causer')
                ->where(function ($builder) use ($term) {
                    $builder->where('description', 'like', $term)
                        ->orWhere('event', 'like', $term);
                })
                ->latest()
                ->limit(6)
                ->get()
                ->filter(fn (Activity $record) => $this->canViewActivitySubject($user, $record))
                ->map(fn (Activity $record) => [
                    'title' => $record->description ?: 'Activity recorded',
                    'description' => ($record->causer?->name ?? 'System').' • '.($record->created_at?->format('M d, Y h:i A') ?? 'Recent'),
                    'meta' => $record->event ? str($record->event)->headline()->toString() : 'Activity',
                    'url' => route('admin.activity.index', array_filter([
                        'event' => $record->event,
                    ])),
                ]);

            if ($items->isNotEmpty()) {
                $groups[] = [
                    'title' => 'Activity',
                    'icon' => 'filter',
                    'items' => $items->all(),
                ];
            }
        }

        if ($this->shouldSearchDomain('notifications', $allowedDomains)) {
            $notificationItems = $user->notifications()
                ->where('data', 'like', $term)
                ->latest()
                ->limit(6)
                ->get()
                ->map(function ($record) {
                    $title = data_get($record->data, 'title', 'Notification');
                    $message = data_get($record->data, 'message', 'Open notifications to view details.');

                    return [
                        'title' => $title,
                        'description' => $message,
                        'meta' => $record->read_at ? 'Read' : 'Unread',
                        'url' => route('admin.notifications.index'),
                    ];
                });

            if ($notificationItems->isNotEmpty()) {
                $groups[] = [
                    'title' => 'Notifications',
                    'icon' => 'bell',
                    'items' => $notificationItems->all(),
                ];
            }
        }

        if ($this->shouldSearchDomain('settings', $allowedDomains) && $user->can('viewAny', Setting::class)) {
            $items = Setting::query()
                ->where(function ($builder) use ($term) {
                    $builder->where('key', 'like', $term)
                        ->orWhere('value', 'like', $term);
                })
                ->limit(6)
                ->get()
                ->map(fn (Setting $record) => [
                    'title' => str($record->key)->replace('_', ' ')->headline()->toString(),
                    'description' => str((string) $record->value)->limit(60)->toString(),
                    'meta' => 'Setting',
                    'url' => route('admin.settings.index'),
                ]);

            if ($items->isNotEmpty()) {
                $groups[] = [
                    'title' => 'Settings',
                    'icon' => 'palette',
                    'items' => $items->all(),
                ];
            }
        }

        return [$groups, $this->domainLabels($restrictedDomains)->all()];
    }

    protected function requestedDomains(string $query): Collection
    {
        $normalized = Str::of($query)->lower()->replaceMatches('/[^a-z0-9\s]/', ' ')->toString();
        $keywords = [
            'users' => ['user', 'users', 'account', 'accounts', 'member', 'members'],
            'roles' => ['role', 'roles'],
            'permissions' => ['permission', 'permissions', 'capability', 'capabilities', 'privilege', 'privileges', 'access'],
            'activity' => ['activity', 'activities', 'audit', 'log', 'logs', 'event', 'events', 'report', 'reports'],
            'notifications' => ['notification', 'notifications', 'alert', 'alerts', 'message', 'messages'],
            'settings' => ['setting', 'settings', 'theme', 'themes', 'branding', 'brand', 'logo', 'favicon'],
        ];

        return collect($keywords)
            ->filter(fn (array $terms) => collect($terms)->contains(fn (string $term) => preg_match('/\b'.preg_quote($term, '/').'\b/', $normalized) === 1))
            ->keys()
            ->values();
    }

    protected function shouldSearchDomain(string $domain, Collection $requestedDomains): bool
    {
        return $requestedDomains->isEmpty() || $requestedDomains->contains($domain);
    }

    protected function canSearchDomain(User $user, string $domain): bool
    {
        return match ($domain) {
            'users' => $user->can('viewAny', User::class),
            'roles' => $user->can('viewAny', Role::class),
            'permissions' => $user->can('viewAny', Permission::class),
            'activity' => $user->isSuperAdmin(),
            'notifications' => $user->isSuperAdmin(),
            'settings' => $user->can('viewAny', Setting::class),
            default => false,
        };
    }

    protected function canViewActivitySubject(User $user, Activity $activity): bool
    {
        return match ($activity->subject_type) {
            User::class => $user->can('viewAny', User::class),
            Role::class => $user->can('viewAny', Role::class),
            Permission::class => $user->can('viewAny', Permission::class),
            Setting::class => $user->can('viewAny', Setting::class),
            null => true,
            default => $user->can('read-dashboard'),
        };
    }

    protected function domainLabels(Collection $domains): Collection
    {
        return $domains
            ->map(fn (string $domain) => match ($domain) {
                'users' => 'users',
                'roles' => 'roles',
                'permissions' => 'permissions',
                'activity' => 'activity',
                'notifications' => 'notifications',
                'settings' => 'settings',
                default => $domain,
            })
            ->values();
    }
}