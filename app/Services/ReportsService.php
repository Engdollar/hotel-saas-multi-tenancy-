<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\Tenancy\CurrentCompanyContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class ReportsService
{
    public function __construct(protected CurrentCompanyContext $companyContext)
    {
    }

    public function filtersFromRequest(Request $request): array
    {
        $dateTo = $request->date('date_to')?->endOfDay() ?? now()->endOfDay();
        $dateFrom = $request->date('date_from')?->startOfDay() ?? now()->subDays(29)->startOfDay();

        if ($dateFrom->greaterThan($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo->copy()->startOfDay(), $dateFrom->copy()->endOfDay()];
        }

        return [
            'date_from' => CarbonImmutable::instance($dateFrom),
            'date_to' => CarbonImmutable::instance($dateTo),
        ];
    }

    public function activityQuery(array $filters)
    {
        return Activity::query()
            ->with('causer')
            ->whereBetween('created_at', [$filters['date_from'], $filters['date_to']])
            ->when(
                ! $this->companyContext->bypassesTenancy() && $this->companyContext->id() !== null,
                fn ($query) => $query->where('company_id', $this->companyContext->id())
            )
            ->latest();
    }

    public function summary(array $filters): array
    {
        $activities = $this->activityQuery($filters)->get();

        return [
            ['label' => 'Events', 'value' => $activities->count(), 'description' => 'Activity entries in range'],
            ['label' => 'New Users', 'value' => User::query()->whereBetween('created_at', [$filters['date_from'], $filters['date_to']])->count(), 'description' => 'Accounts created in range'],
            ['label' => 'New Roles', 'value' => Role::query()->whereBetween('created_at', [$filters['date_from'], $filters['date_to']])->count(), 'description' => 'Roles created in range'],
            ['label' => 'Permissions', 'value' => Permission::count(), 'description' => 'Current permission inventory'],
        ];
    }

    public function activityTrend(array $filters): array
    {
        $days = collect(range(0, $filters['date_from']->diffInDays($filters['date_to'])))->map(
            fn (int $offset) => $filters['date_from']->addDays($offset)
        );

        $grouped = $this->activityQuery($filters)
            ->get()
            ->groupBy(fn (Activity $activity) => $activity->created_at?->format('Y-m-d'));

        return [
            'labels' => $days->map(fn (CarbonImmutable $day) => $day->format('M d'))->all(),
            'values' => $days->map(fn (CarbonImmutable $day) => $grouped->get($day->format('Y-m-d'), collect())->count())->all(),
        ];
    }

    public function roleDistribution(): array
    {
        $roles = Role::query()->withCount('users')->orderByDesc('users_count')->get();

        return [
            'labels' => $roles->pluck('name')->all(),
            'values' => $roles->pluck('users_count')->all(),
        ];
    }

    public function moduleBreakdown(array $filters): Collection
    {
        return $this->activityQuery($filters)
            ->get()
            ->groupBy(fn (Activity $activity) => $this->moduleName($activity))
            ->map(function (Collection $activities, string $module) {
                $latest = $activities->sortByDesc('created_at')->first();

                return [
                    'module' => $module,
                    'count' => $activities->count(),
                    'latest_event' => str($latest?->event ?: $latest?->description ?: 'recorded')->headline()->toString(),
                    'latest_at' => $latest?->created_at?->format('M d, Y h:i A') ?? 'Recent',
                ];
            })
            ->sortByDesc('count')
            ->values();
    }

    public function exportRows(array $filters): Collection
    {
        return $this->activityQuery($filters)
            ->get()
            ->map(fn (Activity $activity) => [
                $activity->created_at?->format('Y-m-d H:i'),
                $activity->causer?->name ?? 'System',
                class_basename((string) $activity->subject_type) ?: 'System',
                (string) ($activity->event ?: 'recorded'),
                (string) ($activity->description ?: 'Activity recorded'),
            ]);
    }

    public function intelligence(): array
    {
        $roles = Role::query()->withCount(['permissions', 'users'])->get();
        $permissions = Permission::query()->withCount('roles')->get();
        $users = User::query()->withCount('roles')->get();
        $recentActivities = Activity::query()
            ->when(
                ! $this->companyContext->bypassesTenancy() && $this->companyContext->id() !== null,
                fn ($query) => $query->where('company_id', $this->companyContext->id())
            )
            ->latest()
            ->take(8)
            ->get();

        return [
            'highlights' => [
                ['label' => 'Users without roles', 'value' => $users->where('roles_count', 0)->count(), 'description' => 'Accounts needing assignment'],
                ['label' => 'Dormant roles', 'value' => $roles->where('users_count', 0)->count(), 'description' => 'Roles with no assigned users'],
                ['label' => 'Unused permissions', 'value' => $permissions->where('roles_count', 0)->count(), 'description' => 'Capabilities not attached to roles'],
                [
                    'label' => '24h activity',
                    'value' => Activity::query()
                        ->where('created_at', '>=', now()->subDay())
                        ->when(
                            ! $this->companyContext->bypassesTenancy() && $this->companyContext->id() !== null,
                            fn ($query) => $query->where('company_id', $this->companyContext->id())
                        )
                        ->count(),
                    'description' => 'Events in the last day',
                ],
            ],
            'roleRisk' => $roles->sortByDesc(fn (Role $role) => $role->permissions_count + ($role->users_count * 2))->take(5)->values(),
            'recommendations' => collect([
                $users->where('roles_count', 0)->count() > 0 ? 'Assign roles to users without RBAC coverage.' : null,
                $roles->where('permissions_count', '>', 10)->count() > 0 ? 'Review broad roles with large permission surfaces.' : null,
                $permissions->where('roles_count', 0)->count() > 0 ? 'Retire or map unused permissions into active roles.' : null,
                Activity::query()
                    ->where('created_at', '>=', now()->subDay())
                    ->when(
                        ! $this->companyContext->bypassesTenancy() && $this->companyContext->id() !== null,
                        fn ($query) => $query->where('company_id', $this->companyContext->id())
                    )
                    ->count() > 50
                        ? 'Activity volume is elevated today. Review the audit stream.'
                        : 'Activity volume is within the normal range.',
            ])->filter()->values(),
            'recentActivities' => $recentActivities,
            'activityTrend' => $this->activityTrend([
                'date_from' => now()->subDays(13)->startOfDay()->toImmutable(),
                'date_to' => now()->endOfDay()->toImmutable(),
            ]),
            'roleDistribution' => $this->roleDistribution(),
        ];
    }

    public function moduleName(Activity $activity): string
    {
        return match (class_basename((string) $activity->subject_type)) {
            'User' => 'Users',
            'Role' => 'Roles',
            'Permission' => 'Permissions',
            'Setting' => 'Settings',
            '' => 'System',
            default => str(class_basename((string) $activity->subject_type))->headline()->plural()->toString(),
        };
    }
}