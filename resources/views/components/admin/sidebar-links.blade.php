@props(['mobile' => false])

@php
    $isSuperAdmin = auth()->user()?->isSuperAdmin();

    $tenantRouteModule = request()->route('module');
    $tenantRouteName = request()->route()?->getName();
    $superAdminSections = [
        [
            'label' => 'Workspace',
            'items' => [
                ['label' => 'Dashboard', 'route' => route('admin.dashboard'), 'icon' => 'sparkles', 'can' => 'read-dashboard', 'active' => request()->routeIs('admin.dashboard')],
                ['label' => 'Users', 'route' => route('admin.users.index'), 'icon' => 'plus', 'can' => 'read-user', 'active' => request()->routeIs('admin.users.*')],
                ['label' => 'Roles', 'route' => route('admin.roles.index'), 'icon' => 'check-square', 'can' => 'read-role', 'active' => request()->routeIs('admin.roles.*')],
                ['label' => 'Permissions', 'route' => route('admin.permissions.index'), 'icon' => 'settings', 'can' => 'read-permission', 'active' => request()->routeIs('admin.permissions.*')],
                ['label' => 'Support Tickets', 'route' => route('admin.tickets.index'), 'icon' => 'activity', 'can' => 'read-ticket', 'active' => request()->routeIs('admin.tickets.*')],
            ],
        ],
        [
            'label' => 'Platform Control',
            'items' => [
                ['label' => 'Companies', 'route' => route('admin.companies.index'), 'icon' => 'layers', 'active' => request()->routeIs('admin.companies.*')],
                ['label' => 'Reports', 'route' => route('admin.reports.index'), 'icon' => 'filter', 'active' => request()->routeIs('admin.reports.*')],
                ['label' => 'Intelligence', 'route' => route('admin.intelligence.index'), 'icon' => 'sparkles', 'active' => request()->routeIs('admin.intelligence.*')],
                ['label' => 'Notifications', 'route' => route('admin.notifications.index'), 'icon' => 'bell', 'active' => request()->routeIs('admin.notifications.*')],
                ['label' => 'Activity', 'route' => route('admin.activity.index'), 'icon' => 'filter', 'active' => request()->routeIs('admin.activity.*')],
            ],
        ],
    ];

    $tenantItemIsActive = static function (array $item) use ($tenantRouteModule, $tenantRouteName): bool {
        if (($item['key'] ?? null) === 'dashboard') {
            return request()->routeIs('admin.dashboard');
        }

        if (($item['key'] ?? null) === 'company-profile') {
            return request()->routeIs('admin.company-profile.*');
        }

        if (($item['key'] ?? null) === 'tickets') {
            return request()->routeIs('admin.tickets.*');
        }

        if (($item['key'] ?? null) !== null && $tenantRouteModule === $item['key'] && str_starts_with((string) $tenantRouteName, 'admin.workspace.')) {
            return true;
        }

        return request()->url() === $item['route'];
    };
@endphp

<nav {{ $attributes->merge(['class' => $mobile ? 'mt-4 space-y-1.5' : 'flex-1 space-y-1.5 px-4 py-5']) }}>
    @if ($isSuperAdmin)
        @foreach ($superAdminSections as $section)
            <div class="px-2 pt-3 text-[11px] font-semibold uppercase tracking-[0.2em] text-muted">{{ $section['label'] }}</div>
            @foreach ($section['items'] as $item)
                @if (! isset($item['can']) || auth()->user()?->can($item['can']))
                    <a href="{{ $item['route'] }}" @if ($mobile) @click="closeMobileNav" @endif class="sidebar-link {{ $item['active'] ? 'sidebar-link-active' : '' }}"><x-icon :name="$item['icon']" class="h-4 w-4" />{{ $item['label'] }}</a>
                @endif
            @endforeach
        @endforeach
    @else
        @foreach ($tenantWorkspaceNavigation as $section)
            <div class="px-2 pt-3 text-[11px] font-semibold uppercase tracking-[0.2em] text-muted">{{ $section['label'] }}</div>
            @foreach ($section['items'] as $item)
                <a href="{{ $item['route'] }}" @if ($mobile) @click="closeMobileNav" @endif class="sidebar-link {{ $tenantItemIsActive($item) ? 'sidebar-link-active' : '' }}">
                    <x-icon :name="$item['icon']" class="h-4 w-4" />
                    <span class="flex-1">{{ $item['label'] }}</span>
                    @if (! empty($item['badge']))
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold" style="background: rgba(245, 158, 11, 0.16); color: var(--text-primary);">{{ $item['badge'] }}</span>
                    @endif
                </a>
            @endforeach
        @endforeach
    @endif

    <a href="{{ route('profile.edit') }}" @if ($mobile) @click="closeMobileNav" @endif class="sidebar-link {{ request()->routeIs('profile.*') ? 'sidebar-link-active' : '' }}"><x-icon name="eye" class="h-4 w-4" />Profile</a>

    <a href="{{ route('documentation.index') }}" @if ($mobile) @click="closeMobileNav" @endif class="sidebar-link {{ request()->routeIs('documentation.*') ? 'sidebar-link-active' : '' }}">
        <x-icon name="layers" class="h-4 w-4" />Documentation
    </a>

    @can('read-setting')
        <a href="{{ route('admin.settings.index') }}" @if ($mobile) @click="closeMobileNav" @endif class="sidebar-link {{ request()->routeIs('admin.settings.*') ? 'sidebar-link-active' : '' }}">
            <x-icon name="palette" class="h-4 w-4" />Settings
        </a>
    @endcan
</nav>