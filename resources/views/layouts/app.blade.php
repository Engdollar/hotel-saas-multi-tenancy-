@php
    $themeMode = (string) $appSettings->get('theme_mode', 'dark');
    $themePreset = (string) $appSettings->get('theme_preset', 'cleopatra');
    $allowPresetPreview = request()->routeIs('admin.settings.*');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme-preset="{{ $themePreset }}" @class(['dark' => $themeMode === 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $appSettings->get('project_title', config('app.name', 'Laravel')) }}</title>
        @if ($appSettings->get('favicon'))
            <link rel="icon" type="image/png" href="{{ \App\Support\AssetPath::storageUrl($appSettings->get('favicon')) }}">
        @endif

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument+sans:400,500,600,700,800&display=swap" rel="stylesheet" />

        <script>
            (() => {
                const root = document.documentElement;
                const mode = @json($themeMode);
                const preset = @json($themePreset);
                const allowPresetPreview = @json($allowPresetPreview);
                const previewSignatureKey = 'theme-preview-default-signature';
                const previewSignature = `${mode}:${preset}`;
                const storedSignature = localStorage.getItem(previewSignatureKey);

                if (storedSignature !== previewSignature) {
                    localStorage.removeItem('theme-preview-mode');
                    localStorage.removeItem('theme-preview-preset');
                    localStorage.setItem(previewSignatureKey, previewSignature);
                }

                const resolvedMode = localStorage.getItem('theme-preview-mode') ?? mode;
                const resolvedPreset = allowPresetPreview
                    ? (localStorage.getItem('theme-preview-preset') ?? preset)
                    : preset;

                if (!allowPresetPreview) {
                    localStorage.removeItem('theme-preview-preset');
                }

                root.dataset.themePreset = resolvedPreset;
                root.classList.toggle('dark', resolvedMode === 'dark' || (resolvedMode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches));
            })();
        </script>

        <!-- Scripts -->
        <x-app-assets />
        @if (! empty($appThemePresetStyles))
            <style>
{!! $appThemePresetStyles !!}
            </style>
        @endif
    </head>
    @php
        $initialToasts = collect([
            session('success') ? ['type' => 'success', 'message' => session('success')] : null,
            session('error') ? ['type' => 'error', 'message' => session('error')] : null,
        ])->filter()->values();

        $resolvedBreadcrumbs = $breadcrumbs ?? null;

        if ($resolvedBreadcrumbs === null) {
            $routeName = request()->route()?->getName();
            $segments = collect(explode('.', (string) $routeName))
                ->filter()
                ->values();

            if ($segments->first() === 'admin') {
                $segments = $segments->slice(1)->values();
            }

            $resolvedBreadcrumbs = collect();

            if (! $routeName || $routeName !== 'admin.dashboard') {
                $resolvedBreadcrumbs->push(['label' => 'Dashboard', 'url' => route('admin.dashboard')]);
            }

            $baseParts = [];

            foreach ($segments as $index => $segment) {
                $baseParts[] = $segment;
                $isLast = $index === $segments->count() - 1;

                if ($segment === 'index') {
                    continue;
                }

                $label = match ($segment) {
                    'create' => 'Create',
                    'edit' => 'Edit',
                    'show' => 'Details',
                    default => str($segment)->replace('-', ' ')->replace('_', ' ')->headline()->toString(),
                };

                $candidate = collect($baseParts)
                    ->reject(fn ($part) => in_array($part, ['create', 'edit', 'show', 'index'], true))
                    ->join('.');

                $routeCandidate = null;

                if ($candidate) {
                    $routeCandidate = str_starts_with((string) $routeName, 'admin.')
                        ? 'admin.'.$candidate.'.index'
                        : $candidate.'.index';
                }

                $url = null;

                if (! $isLast && $routeCandidate && \Illuminate\Support\Facades\Route::has($routeCandidate)) {
                    $url = route($routeCandidate);
                }

                $resolvedBreadcrumbs->push(array_filter([
                    'label' => $label,
                    'url' => $url,
                ]));
            }

            $resolvedBreadcrumbs = $resolvedBreadcrumbs->values()->all();
        }
    @endphp
    <body
        class="font-sans antialiased"
        data-theme-mode="{{ $themeMode }}"
        data-theme-preset="{{ $themePreset }}"
        data-allow-preset-preview="{{ $allowPresetPreview ? 'true' : 'false' }}"
        data-search-index-url="{{ route('admin.search.index') }}"
        data-search-query="{{ request()->routeIs('admin.search.index') ? request('query', '') : '' }}"
        data-initial-toasts='@json($initialToasts)'
        x-data='themeManager({ mode: $el.dataset.themeMode, preset: $el.dataset.themePreset, allowPresetPreview: $el.dataset.allowPresetPreview === "true", searchIndexUrl: $el.dataset.searchIndexUrl, initialSearchQuery: $el.dataset.searchQuery, initialToasts: $el.dataset.initialToasts ? JSON.parse($el.dataset.initialToasts) : [] })'
    >
        <div class="min-h-screen">
            <div x-show="uiLoading" x-cloak class="loading-overlay">
                <div class="loading-card">
                    <span class="loader-spinner"></span>
                    <p class="text-sm font-semibold" style="color: var(--text-primary);" x-text="loadingMessage"></p>
                </div>
            </div>

            <div x-show="modalOpen" x-cloak x-transition.opacity class="crud-modal-backdrop" @click.self="closeCrudModal()">
                <div class="crud-modal-shell">
                    <div class="crud-modal-card panel" x-ref="modalContent"></div>
                </div>
            </div>

            <div class="toast-stack" aria-live="polite" aria-atomic="true">
                <template x-for="toast in toasts" :key="toast.id">
                    <div class="toast-item" :class="toast.type === 'error' ? 'is-error' : 'is-success'" x-show="toast.visible" x-transition>
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full" :class="toast.type === 'error' ? 'toast-icon is-error' : 'toast-icon is-success'">
                                <x-icon name="sparkles" class="h-4 w-4" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold" style="color: var(--text-primary);" x-text="toast.type === 'error' ? 'Action blocked' : 'Done'"></p>
                                <p class="mt-1 text-sm text-muted" x-text="toast.message"></p>
                            </div>
                            <button type="button" class="icon-button h-8 w-8" @click="dismissToast(toast.id)" aria-label="Dismiss notification">
                                <x-icon name="x" class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="mobileNavOpen" x-cloak class="fixed inset-0 z-[110] bg-slate-950/40 lg:hidden" @click="closeMobileNav"></div>

            <aside x-show="mobileNavOpen" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full opacity-0" x-transition:enter-end="translate-x-0 opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0 opacity-100" x-transition:leave-end="-translate-x-full opacity-0" class="mobile-sidebar fixed inset-y-0 left-0 z-[120] flex w-[86vw] max-w-[320px] flex-col overflow-y-auto px-4 py-4 backdrop-blur lg:hidden">
                <div class="panel p-4">
                    <div class="flex items-center justify-between gap-3">
                        <a href="{{ route('admin.dashboard') }}" class="brand-lockup" @click="closeMobileNav">
                            @if ($appSettings->get('logo'))
                                <img src="{{ \App\Support\AssetPath::storageUrl($appSettings->get('logo')) }}" alt="{{ $appSettings->get('project_title', config('app.name')) }}" class="h-10 w-10 rounded-2xl object-contain">
                            @else
                                <x-application-logo />
                            @endif
                            <div class="min-w-0">
                                <p class="truncate text-sm font-black uppercase tracking-[0.18em]" style="color: var(--text-primary);">{{ $appSettings->get('project_title', config('app.name')) }}</p>
                                <p class="mt-1 text-xs text-muted">{{ $workspaceLabel }}</p>
                            </div>
                        </a>
                        <button type="button" class="icon-button" @click="closeMobileNav" aria-label="Close navigation">
                            <x-icon name="x" class="h-4 w-4" />
                        </button>
                    </div>

                    <x-admin.sidebar-links mobile />

                    <div class="mt-4">
                        <x-admin.user-menu :full-width="true" />
                    </div>
                </div>
            </aside>

            <div class="app-shell mx-auto flex min-h-screen max-w-[1600px] gap-4 px-3 py-3 transition-all lg:px-5" :class="sidebarCollapsed ? 'lg:gap-0' : 'lg:gap-4'">
                <aside class="app-sidebar panel hidden shrink-0 overflow-hidden transition-all duration-200 lg:flex lg:flex-col" :class="sidebarCollapsed ? 'lg:w-0 lg:-translate-x-4 lg:border-transparent lg:opacity-0' : 'lg:w-72 lg:opacity-100'">
                    <div class="border-b px-5 py-5" style="border-color: var(--panel-border);">
                        <a href="{{ route('admin.dashboard') }}" class="brand-lockup">
                            @if ($appSettings->get('logo'))
                                <img src="{{ \App\Support\AssetPath::storageUrl($appSettings->get('logo')) }}" alt="{{ $appSettings->get('project_title', config('app.name')) }}" class="h-11 w-11 rounded-2xl object-contain">
                            @else
                                <x-application-logo />
                            @endif
                            <div class="min-w-0">
                                <p class="truncate text-sm font-black uppercase tracking-[0.22em]" style="color: var(--text-primary);">{{ $appSettings->get('project_title', config('app.name')) }}</p>
                                <p class="mt-1 text-xs text-muted">{{ $workspaceLabel }}</p>
                            </div>
                        </a>
                    </div>

                    <x-admin.sidebar-links />
                </aside>

                @isset($subSidebar)
                    <aside class="settings-sub-sidebar panel hidden xl:flex xl:w-64 xl:flex-col">
                        {{ $subSidebar }}
                    </aside>
                @endisset

                <div class="flex min-h-screen flex-1 flex-col gap-6">
                    <header class="app-topbar panel relative z-40 flex flex-col gap-3 overflow-visible px-3 py-2.5 sm:px-4 sm:py-3 lg:px-5">
                        <div class="flex items-center justify-between gap-2 sm:gap-3">
                            <div class="flex min-w-0 items-center gap-2 sm:gap-3 lg:flex-1">
                                <button type="button" class="icon-button lg:hidden" @click="toggleMobileNav" aria-label="Open navigation">
                                    <x-icon name="menu" class="h-4 w-4" />
                                </button>
                                <button type="button" class="icon-button hidden lg:inline-flex" @click="toggleSidebar" :aria-label="sidebarCollapsed ? 'Show sidebar' : 'Hide sidebar'" :title="sidebarCollapsed ? 'Show sidebar' : 'Hide sidebar'">
                                    <x-icon name="menu" class="h-4 w-4" />
                                </button>
                                <a href="{{ route('admin.dashboard') }}" class="brand-lockup min-w-0 flex-1 px-1 lg:hidden">
                                    @if ($appSettings->get('logo'))
                                        <img src="{{ \App\Support\AssetPath::storageUrl($appSettings->get('logo')) }}" alt="{{ $appSettings->get('project_title', config('app.name')) }}" class="h-9 w-9 rounded-2xl object-contain">
                                    @else
                                        <x-application-logo />
                                    @endif
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-black uppercase tracking-[0.18em]" style="color: var(--text-primary);">{{ $appSettings->get('project_title', config('app.name')) }}</p>
                                        <p class="mt-1 text-xs text-muted">Admin Panel</p>
                                    </div>
                                </a>

                                <form action="{{ route('admin.search.index') }}" method="GET" class="header-search-shell hidden lg:flex lg:max-w-[26rem] xl:max-w-[30rem]" @submit.prevent="submitSearch()">
                                    <span class="header-search-icon">
                                        <x-icon name="search" class="h-4 w-4" />
                                    </span>
                                    <input type="search" name="query" x-model="searchQuery" @input="handleSearchInput" class="header-search-input" placeholder="Search the admin workspace">
                                    <button type="submit" class="header-search-submit" aria-label="Search">
                                        <x-icon name="arrow-right" class="h-4 w-4" />
                                    </button>
                                </form>
                            </div>

                            <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                                @if (auth()->user()?->isSuperAdmin())
                                    <form method="POST" action="{{ route('admin.companies.switch') }}" class="hidden lg:block">
                                        @csrf
                                        <label for="company_switcher" class="sr-only">Company scope</label>
                                        <select id="company_switcher" name="company_id" onchange="this.form.submit()" class="rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-800">
                                            <option value="0">All companies</option>
                                            @foreach ($availableCompanies as $companyOption)
                                                <option value="{{ $companyOption->id }}" @selected($activeCompany?->id === $companyOption->id)>{{ $companyOption->name }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                @endif

                                <button type="button" class="icon-button lg:hidden" @click="toggleMobileSearch" aria-label="Toggle search">
                                    <x-icon name="search" class="h-4 w-4" />
                                </button>

                                <button type="button" @click="toggleMode" class="icon-button" :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'" :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'">
                                    <x-icon x-show="!isDark" name="moon" class="h-4 w-4" />
                                    <x-icon x-show="isDark" name="sun" class="h-4 w-4" />
                                </button>

                                @role('Super Admin')
                                    <div class="relative" x-data="{ open: false }">
                                        <button type="button" @click="open = !open" class="icon-button relative" aria-label="Alerts">
                                            <x-icon name="bell" class="h-4 w-4" />
                                            @if (auth()->user()->unreadNotifications()->count())
                                                <span class="absolute -right-1 -top-1 rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white">{{ auth()->user()->unreadNotifications()->count() }}</span>
                                            @endif
                                        </button>
                                        <div x-show="open" x-cloak @click.outside="open = false" x-transition class="popover-panel absolute right-0 z-[90] mt-3 w-[min(20rem,85vw)] p-4">
                                            <div class="mb-3 flex items-center justify-between">
                                                <h2 class="text-sm font-semibold" style="color: var(--text-primary);">Recent notifications</h2>
                                                <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                                                    @csrf
                                                    <button type="submit" class="text-xs font-semibold" style="color: var(--accent);">Mark all read</button>
                                                </form>
                                            </div>
                                            <div class="space-y-3">
                                                @forelse (auth()->user()->notifications()->take(5)->get() as $notification)
                                                    <div class="surface-soft p-3">
                                                        <p class="text-sm font-semibold" style="color: var(--text-primary);">{{ $notification->data['title'] ?? 'System update' }}</p>
                                                        <p class="mt-1 text-sm text-muted">{{ $notification->data['message'] ?? '' }}</p>
                                                        @if (! $notification->read_at)
                                                            <form method="POST" action="{{ route('admin.notifications.read', $notification) }}" class="mt-2">
                                                                @csrf
                                                                <button type="submit" class="text-xs font-semibold" style="color: var(--accent);">Mark as read</button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                @empty
                                                    <p class="text-sm text-muted">No notifications yet.</p>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                @endrole

                                <div class="hidden lg:block">
                                    <x-admin.user-menu />
                                </div>

                                <div class="lg:hidden">
                                    <x-admin.user-menu compact />
                                </div>
                            </div>
                        </div>

                        <div x-show="mobileSearchOpen" x-cloak x-transition class="mobile-search-panel lg:hidden">
                            <form action="{{ route('admin.search.index') }}" method="GET" @submit.prevent="submitSearch()" class="header-search-shell mobile-search-shell">
                                <span class="header-search-icon">
                                    <x-icon name="search" class="h-4 w-4" />
                                </span>
                                <input type="search" name="query" x-ref="mobileSearchInput" x-model="searchQuery" @input="handleSearchInput" class="header-search-input" placeholder="Search the admin workspace">
                                <button type="submit" class="header-search-submit" aria-label="Search">
                                    <x-icon name="arrow-right" class="h-4 w-4" />
                                </button>
                            </form>
                        </div>

                        <div class="flex flex-col gap-2 border-t pt-2.5 sm:pt-3" style="border-color: var(--panel-border);">
                            <div class="page-header-row flex items-center justify-between gap-3 flex-nowrap">
                                <div class="page-heading">
                                    @isset($header)
                                        {{ $header }}
                                    @else
                                        <h1>{{ $appSettings->get('project_title', config('app.name', 'Laravel')) }}</h1>
                                    @endisset
                                </div>
                                <div class="page-header-inline inline-flex items-center gap-2 whitespace-nowrap flex-nowrap">
                                    <x-breadcrumbs :items="$resolvedBreadcrumbs" class="page-breadcrumbs inline-flex items-center whitespace-nowrap flex-nowrap" />
                                    @isset($headerActions)
                                        <div class="page-actions inline-flex items-center whitespace-nowrap flex-nowrap">
                                            {{ $headerActions }}
                                        </div>
                                    @endisset
                                </div>
                            </div>
                        </div>
                    </header>

                    <main class="space-y-6">
                        {{ $slot }}
                    </main>
                </div>
            </div>
        </div>
    </body>
</html>
