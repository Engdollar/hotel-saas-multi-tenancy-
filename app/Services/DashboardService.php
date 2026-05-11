<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Support\AssetPath;
use App\Support\Tenancy\CurrentCompanyContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class DashboardService
{
    public function __construct(
        protected SettingsService $settingsService,
        protected ReportsService $reportsService,
        protected TenantWorkspaceService $tenantWorkspaceService,
        protected CurrentCompanyContext $companyContext,
    ) {
    }

    public function iconOptions(): array
    {
        return [
            'user' => 'User',
            'users' => 'Users',
            'shield' => 'Shield',
            'database' => 'Database',
            'bell' => 'Bell',
            'layers' => 'Layers',
            'chart-bar' => 'Bar',
            'pie-chart' => 'Pie',
            'activity' => 'Activity',
            'sparkles' => 'Sparkles',
            'settings' => 'Settings',
            'image' => 'Image',
        ];
    }

    public function chartTypeOptions(): array
    {
        return [
            'line' => 'Line',
            'pie' => 'Pie',
            'bar' => 'Bar',
        ];
    }

    public function statSources(): array
    {
        return [
            'total_users' => [
                'label' => 'Total users',
                'description' => 'Count all registered accounts.',
                'table' => 'users',
            ],
            'total_roles' => [
                'label' => 'Total roles',
                'description' => 'Count RBAC roles.',
                'table' => 'roles',
            ],
            'total_permissions' => [
                'label' => 'Total permissions',
                'description' => 'Count permission records.',
                'table' => 'permissions',
            ],
            'unread_alerts' => [
                'label' => 'Unread alerts',
                'description' => 'Unread notifications for the current user.',
                'table' => 'notifications',
            ],
            'activity_24h' => [
                'label' => 'Activity in 24h',
                'description' => 'Audit log events in the last day.',
                'table' => 'activity_log',
            ],
            'users_without_roles' => [
                'label' => 'Users without roles',
                'description' => 'Accounts missing any assigned role.',
                'table' => 'users / model_has_roles',
            ],
            'dormant_roles' => [
                'label' => 'Dormant roles',
                'description' => 'Roles that currently have zero users.',
                'table' => 'roles / model_has_roles',
            ],
            'unused_permissions' => [
                'label' => 'Unused permissions',
                'description' => 'Permissions not assigned to any role.',
                'table' => 'permissions / role_has_permissions',
            ],
        ];
    }

    public function chartSources(): array
    {
        return [
            'activity_trend_14d' => [
                'label' => 'Activity trend',
                'description' => 'Daily audit volume for the last 14 days.',
                'table' => 'activity_log',
                'types' => ['line', 'pie',  'bar'],
                'default_type' => 'line',
                'default_title' => 'Activity over time',
                'default_badge' => '14 days',
                'audience' => 'super-admin',
                'dataset_label' => 'Events',
            ],
            'role_distribution' => [
                'label' => 'Role distribution',
                'description' => 'Assigned users by role.',
                'table' => 'roles / model_has_roles',
                'types' => ['pie', 'bar'],
                'default_type' => 'pie',
                'default_title' => 'Role distribution',
                'default_badge' => 'Live',
                'audience' => 'all',
                'dataset_label' => 'Users',
            ],
            'new_users_6m' => [
                'label' => 'New users',
                'description' => 'Monthly user growth over the last 6 months.',
                'table' => 'users',
                'types' => ['bar', 'line', 'pie'],
                'default_type' => 'bar',
                'default_title' => 'User growth',
                'default_badge' => '6 months',
                'audience' => 'all',
                'dataset_label' => 'New users',
            ],
            'module_activity_30d' => [
                'label' => 'Module activity',
                'description' => 'Events grouped by subject type in the last 30 days.',
                'table' => 'activity_log',
                'types' => ['bar', 'pie', 'line'],
                'default_type' => 'bar',
                'default_title' => 'Module activity mix',
                'default_badge' => '30 days',
                'audience' => 'super-admin',
                'dataset_label' => 'Events',
            ],
        ];
    }

    public function dashboardStudio(): array
    {
        $settings = $this->settingsService->all();

        return [
            'stats' => $this->sanitizeStatsConfig($this->decodeJsonSetting($settings->get('dashboard_stats')) ?: $this->defaultStatsConfig()),
            'charts' => $this->sanitizeChartsConfig($this->decodeJsonSetting($settings->get('dashboard_charts')) ?: $this->defaultChartsConfig()),
            'auth_visuals' => [
                'login_mode' => (string) $settings->get('auth_login_visual_mode', 'default'),
                'register_mode' => (string) $settings->get('auth_register_visual_mode', 'default'),
                'login_image_url' => AssetPath::storageUrl($settings->get('auth_login_visual_image')),
                'register_image_url' => AssetPath::storageUrl($settings->get('auth_register_visual_image')),
            ],
        ];
    }

    public function widgetDefinitions(User $user): array
    {
        $isSuperAdmin = $user->isSuperAdmin();
        $studio = $this->dashboardStudio();
        $definitions = [];

        if ($studio['stats'] !== []) {
            $definitions['stats'] = ['label' => 'Stats'];
        }

        foreach ($studio['charts'] as $chart) {
            if (($chart['audience'] ?? 'all') === 'super-admin' && ! $isSuperAdmin) {
                continue;
            }

            $definitions[$this->chartWidgetKey($chart['id'])] = [
                'label' => $chart['title'],
            ];
        }

        if ($isSuperAdmin) {
            $definitions['recentActivity'] = ['label' => 'Recent activity'];
        }

        $definitions['quickActions'] = ['label' => 'Quick actions'];

        if ($isSuperAdmin) {
            $definitions['intelligence'] = ['label' => 'Intelligence'];
        }

        return $definitions;
    }

    public function widgetState(User $user): array
    {
        $definitions = $this->widgetDefinitions($user);
        $stored = $user->dashboardPreference?->widgets;

        return collect($definitions)
            ->mapWithKeys(fn (array $definition, string $key) => [$key => true])
            ->merge(is_array($stored) ? array_intersect_key($stored, $definitions) : [])
            ->all();
    }

    public function widgetLayout(User $user): array
    {
        $definitions = $this->widgetDefinitions($user);
        $default = array_keys($definitions);
        $stored = $user->dashboardPreference?->layout;

        if (! is_array($stored) || $stored === []) {
            return $default;
        }

        $known = array_fill_keys($default, true);
        $ordered = array_values(array_filter($stored, fn (mixed $key) => is_string($key) && isset($known[$key])));

        foreach ($default as $key) {
            if (! in_array($key, $ordered, true)) {
                $ordered[] = $key;
            }
        }

        return $ordered;
    }

    public function dragEnabled(User $user): bool
    {
        if (! $user->isSuperAdmin()) {
            return false;
        }

        return (bool) ($user->dashboardPreference?->drag_enabled ?? true);
    }

    public function buildDashboard(User $user): array
    {
        if ($this->tenantWorkspaceService->isTenantWorkspaceUser($user)) {
            return $this->tenantWorkspaceService->buildDashboard($user);
        }

        $isSuperAdmin = $user->isSuperAdmin();
        $subjectLabels = [
            User::class => 'Users',
            Role::class => 'Roles',
            Permission::class => 'Permissions',
            Setting::class => 'Settings',
        ];

        $stats = collect($this->dashboardStudio()['stats'])
            ->map(fn (array $card) => $this->resolveStatCard($card, $user))
            ->values()
            ->all();

        $charts = collect($this->dashboardStudio()['charts'])
            ->filter(fn (array $chart) => ($chart['audience'] ?? 'all') !== 'super-admin' || $isSuperAdmin)
            ->map(fn (array $chart) => $this->resolveChartCard($chart))
            ->values()
            ->all();

        $intelligence = $isSuperAdmin ? $this->reportsService->intelligence() : null;

        return [
            'isTenantWorkspace' => false,
            'pageTitle' => 'Dashboard',
            'pageDescription' => 'Configurable stats, clean charts, and drag-ready widgets.',
            'stats' => $stats,
            'quickActions' => $this->superAdminQuickActions($user),
            'chartWidgets' => $charts,
            'widgetDefinitions' => $this->widgetDefinitions($user),
            'widgetState' => $this->widgetState($user),
            'widgetLayout' => $this->widgetLayout($user),
            'dragEnabled' => $this->dragEnabled($user),
            'canDragWidgets' => $isSuperAdmin,
            'recentActivities' => $isSuperAdmin ? $this->activityQuery()->with('causer')->latest()->take(6)->get() : collect(),
            'subjectLabels' => $subjectLabels,
            'intelligencePreview' => $intelligence ? [
                'highlights' => array_slice($intelligence['highlights'], 0, 3),
                'recommendations' => $intelligence['recommendations']->take(3),
            ] : null,
            'tenantSections' => [],
        ];
    }

    protected function superAdminQuickActions(User $user): array
    {
        return collect([
            $user->can('create-user') ? [
                'title' => 'Add User',
                'description' => 'Create a new account and assign roles.',
                'icon' => 'plus',
                'url' => route('admin.users.create'),
                'modal_url' => route('admin.users.create'),
            ] : null,
            $user->can('create-role') ? [
                'title' => 'Create Role',
                'description' => 'Build a new access layer for staff.',
                'icon' => 'check-square',
                'url' => route('admin.roles.create'),
                'modal_url' => route('admin.roles.create'),
            ] : null,
            $user->isSuperAdmin() ? [
                'title' => 'Generate Permissions',
                'description' => 'Refresh the RBAC matrix and super admin coverage.',
                'icon' => 'sparkles',
                'form_action' => route('admin.settings.generate'),
                'loading_message' => 'Generating permissions...',
            ] : null,
            $user->isSuperAdmin() ? [
                'title' => 'Open Reports',
                'description' => 'Filter and export.',
                'icon' => 'filter',
                'url' => route('admin.reports.index'),
                'loading_message' => 'Opening reports...',
            ] : null,
            $user->isSuperAdmin() ? [
                'title' => 'Dashboard Intelligence',
                'description' => 'Open the master page.',
                'icon' => 'sparkles',
                'url' => route('admin.intelligence.index'),
                'loading_message' => 'Opening intelligence layer...',
            ] : null,
        ])->filter()->values()->all();
    }

    public function sanitizeStatsConfig(array $rows): array
    {
        $sources = $this->statSources();
        $icons = $this->iconOptions();
        $fallbackSource = array_key_first($sources);

        return collect($rows)
            ->filter(fn (mixed $row) => is_array($row))
            ->take(8)
            ->values()
            ->map(function (array $row, int $index) use ($sources, $icons, $fallbackSource) {
                $source = array_key_exists((string) ($row['source'] ?? ''), $sources)
                    ? (string) $row['source']
                    : $fallbackSource;
                $label = trim((string) ($row['label'] ?? $sources[$source]['label']));
                $description = trim((string) ($row['description'] ?? $sources[$source]['description']));
                $icon = array_key_exists((string) ($row['icon'] ?? ''), $icons)
                    ? (string) $row['icon']
                    : 'sparkles';

                return [
                    'id' => (string) ($row['id'] ?? 'stat-'.($index + 1)),
                    'label' => $label !== '' ? Str::limit($label, 40, '') : $sources[$source]['label'],
                    'description' => $description !== '' ? Str::limit($description, 80, '') : $sources[$source]['description'],
                    'icon' => $icon,
                    'source' => $source,
                ];
            })
            ->all();
    }

    public function sanitizeChartsConfig(array $rows): array
    {
        $sources = $this->chartSources();
        $fallbackSource = array_key_first($sources);

        return collect($rows)
            ->filter(fn (mixed $row) => is_array($row))
            ->take(6)
            ->values()
            ->map(function (array $row, int $index) use ($sources, $fallbackSource) {
                $source = array_key_exists((string) ($row['source'] ?? ''), $sources)
                    ? (string) $row['source']
                    : $fallbackSource;
                $supportedTypes = $sources[$source]['types'] ?? ['line'];
                $type = in_array((string) ($row['type'] ?? ''), $supportedTypes, true)
                    ? (string) $row['type']
                    : (string) ($sources[$source]['default_type'] ?? $supportedTypes[0]);
                $audience = in_array((string) ($row['audience'] ?? ''), ['all', 'super-admin'], true)
                    ? (string) $row['audience']
                    : (string) ($sources[$source]['audience'] ?? 'all');

                return [
                    'id' => (string) ($row['id'] ?? Str::slug($sources[$source]['default_title'] ?? 'chart-'.$index)),
                    'title' => Str::limit(trim((string) ($row['title'] ?? $sources[$source]['default_title'] ?? $sources[$source]['label'])), 60, ''),
                    'description' => Str::limit(trim((string) ($row['description'] ?? $sources[$source]['description'])), 90, ''),
                    'source' => $source,
                    'type' => $type,
                    'audience' => $audience,
                ];
            })
            ->all();
    }

    protected function resolveStatCard(array $card, User $user): array
    {
        $value = match ($card['source']) {
            'total_users' => User::count(),
            'total_roles' => Role::count(),
            'total_permissions' => Permission::count(),
            'unread_alerts' => $user->unreadNotifications()->count(),
            'activity_24h' => $this->activityQuery()->where('created_at', '>=', now()->subDay())->count(),
            'users_without_roles' => User::doesntHave('roles')->count(),
            'dormant_roles' => Role::query()->doesntHave('users')->count(),
            'unused_permissions' => Permission::query()->doesntHave('roles')->count(),
            default => 0,
        };

        return [
            'id' => $card['id'],
            'label' => $card['label'],
            'value' => $value,
            'description' => $card['description'],
            'icon' => $card['icon'],
            'source' => $this->statSources()[$card['source']]['table'] ?? null,
        ];
    }

    protected function resolveChartCard(array $chart): array
    {
        $payload = match ($chart['source']) {
            'activity_trend_14d' => $this->reportsService->activityTrend([
                'date_from' => now()->subDays(13)->startOfDay()->toImmutable(),
                'date_to' => now()->endOfDay()->toImmutable(),
            ]),
            'role_distribution' => $this->reportsService->roleDistribution(),
            'new_users_6m' => $this->newUsersByMonth(6),
            'module_activity_30d' => $this->moduleActivityBreakdown(30),
            default => ['labels' => [], 'values' => []],
        };

        $sourceMeta = $this->chartSources()[$chart['source']];
        $id = Str::slug($chart['id']);

        return [
            'id' => $id,
            'widget_key' => $this->chartWidgetKey($chart['id']),
            'eyebrow' => Str::headline($chart['type']).' chart',
            'title' => $chart['title'],
            'description' => $chart['description'],
            'badge' => $sourceMeta['default_badge'] ?? 'Live',
            'type' => $chart['type'],
            'canvas_id' => 'dashboard-chart-'.$id,
            'dataset_label' => $sourceMeta['dataset_label'] ?? 'Series',
            'labels' => $payload['labels'] ?? [],
            'values' => $payload['values'] ?? [],
            'source_table' => $sourceMeta['table'] ?? null,
        ];
    }

    protected function newUsersByMonth(int $months): array
    {
        $start = CarbonImmutable::now()->startOfMonth()->subMonths($months - 1);
        $period = collect(range(0, $months - 1))->map(fn (int $offset) => $start->addMonths($offset));
        $grouped = User::query()
            ->where('created_at', '>=', $start)
            ->get()
            ->groupBy(fn (User $user) => $user->created_at?->format('Y-m'));

        return [
            'labels' => $period->map(fn (CarbonImmutable $month) => $month->format('M'))->all(),
            'values' => $period->map(fn (CarbonImmutable $month) => $grouped->get($month->format('Y-m'), collect())->count())->all(),
        ];
    }

    protected function moduleActivityBreakdown(int $days): array
    {
        $modules = $this->reportsService->moduleBreakdown([
            'date_from' => now()->subDays($days - 1)->startOfDay()->toImmutable(),
            'date_to' => now()->endOfDay()->toImmutable(),
        ])->take(6);

        return [
            'labels' => $modules->pluck('module')->all(),
            'values' => $modules->pluck('count')->all(),
        ];
    }

    protected function chartWidgetKey(string $id): string
    {
        return 'chart:'.Str::slug($id);
    }

    protected function decodeJsonSetting(mixed $value): array
    {
        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    protected function defaultStatsConfig(): array
    {
        return [
            ['id' => 'users', 'label' => 'Users', 'description' => 'Registered accounts', 'icon' => 'users', 'source' => 'total_users'],
            ['id' => 'roles', 'label' => 'Roles', 'description' => 'Access layers', 'icon' => 'layers', 'source' => 'total_roles'],
            ['id' => 'permissions', 'label' => 'Permissions', 'description' => 'Granted capabilities', 'icon' => 'shield', 'source' => 'total_permissions'],
            ['id' => 'alerts', 'label' => 'Unread alerts', 'description' => 'Pending notifications', 'icon' => 'bell', 'source' => 'unread_alerts'],
        ];
    }

    protected function defaultChartsConfig(): array
    {
        return [
            ['id' => 'activity-over-time', 'title' => 'Activity over time', 'description' => 'Audit flow for the last 14 days.', 'source' => 'activity_trend_14d', 'type' => 'line', 'audience' => 'super-admin'],
            ['id' => 'roles-distribution', 'title' => 'Role distribution', 'description' => 'Assigned users by role.', 'source' => 'role_distribution', 'type' => 'pie', 'audience' => 'all'],
            ['id' => 'new-users-bar', 'title' => 'User growth', 'description' => 'Monthly new users for the last 6 months.', 'source' => 'new_users_6m', 'type' => 'bar', 'audience' => 'all'],
        ];
    }

    protected function activityQuery()
    {
        return Activity::query()->when(
            ! $this->companyContext->bypassesTenancy() && $this->companyContext->id() !== null,
            fn ($query) => $query->where('company_id', $this->companyContext->id())
        );
    }
}