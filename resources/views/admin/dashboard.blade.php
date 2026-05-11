<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="type-page-title" style="color: var(--text-primary);">{{ $pageTitle }}</h1>
            <p class="type-body mt-1 text-muted">{{ $pageDescription }}</p>
        </div>
    </x-slot>

    @if (! $isTenantWorkspace)
        <x-slot name="headerActions">
            <button type="button" class="btn-secondary icon-label-button header-action-button" @click="$dispatch('toggle-page-controls')">
                <x-icon name="settings" class="h-4 w-4" />
                <span class="sm:hidden">Controls</span>
                <span class="hidden sm:inline">Dashboard controls</span>
            </button>
        </x-slot>
    @endif

    <div x-data='dashboardWidgets({ widgets: @json($widgetState), layout: @json($widgetLayout), available: @json(array_keys($widgetDefinitions)), dragEnabled: @json($dragEnabled), canManageDrag: @json($canDragWidgets), endpoint: "{{ route('admin.dashboard.widgets') }}", csrfToken: "{{ csrf_token() }}", controlsOpen: false, bootDelay: 420 })' @toggle-page-controls.window="toggleControls()" class="space-y-5">
        <div x-show="!ready" class="space-y-5">
            <div class="grid gap-4 lg:grid-cols-4">
                @foreach (range(1, 4) as $index)
                    <div class="skeleton-panel space-y-4">
                        <div class="skeleton-line w-24"></div>
                        <div class="skeleton-line is-title w-20"></div>
                        <div class="skeleton-line w-32"></div>
                    </div>
                @endforeach
            </div>

            <div class="skeleton-panel space-y-4">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <div class="space-y-3">
                        <div class="skeleton-line w-32"></div>
                        <div class="skeleton-line is-title w-64 max-w-full"></div>
                        <div class="skeleton-line w-72 max-w-full"></div>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3 xl:gap-2">
                        @foreach (range(1, 6) as $index)
                            <div class="skeleton-pill w-40 max-w-full"></div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="grid gap-5 xl:grid-cols-2">
                <div class="skeleton-panel space-y-4">
                    <div class="skeleton-line w-28"></div>
                    <div class="skeleton-line is-title w-52"></div>
                    <div class="skeleton-block"></div>
                </div>
                <div class="skeleton-panel space-y-4">
                    <div class="skeleton-line w-24"></div>
                    <div class="skeleton-line is-title w-48"></div>
                    <div class="skeleton-block"></div>
                </div>
            </div>
        </div>

        <div x-show="ready" x-cloak class="space-y-5">
            <div x-show="showControls" x-transition x-cloak class="panel p-4 sm:p-5 lg:p-6" @if($isTenantWorkspace) style="display: none;" @endif>
                <div class="flex flex-col gap-4 lg:gap-5 xl:flex-row xl:items-start xl:justify-between">
                    <div>
                        <p class="section-kicker">Dashboard control</p>
                        <h2 class="type-section-title mt-1" style="color: var(--text-primary);">Show, hide, drag</h2>
                        <p class="type-body mt-2 text-muted">Toggle any widget here, then drag the cards below to switch positions.</p>
                    </div>
                    <div class="widget-toggle-grid widget-toggle-grid-balanced xl:max-w-[46rem] xl:justify-end">
                        @foreach ($widgetDefinitions as $widgetKey => $widget)
                            <button type="button" class="widget-chip" :class="widgets['{{ $widgetKey }}'] ? 'is-active' : ''" @click="toggleWidget('{{ $widgetKey }}')">{{ $widget['label'] }}</button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="dashboard-canvas" x-ref="canvas" x-init="initCanvas()">
                <div data-widget-key="stats" :draggable="dragAllowed" @dragstart="startDrag('stats')" @dragend="endDrag()" @dragover.prevent="dragAllowed" @drop="dropWidget('stats')" x-show="isVisible('stats')" x-cloak class="dashboard-widget is-full" :class="draggingKey === 'stats' ? 'is-dragging' : ''">
                    <div class="panel p-5 sm:p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="section-kicker">Top stats</p>
                                <h2 class="type-section-title mt-1" style="color: var(--text-primary);">Configured summary cards</h2>
                            </div>
                            <span class="dashboard-drag-handle" x-show="dragAllowed" x-cloak>
                                <x-icon name="layers" class="h-4 w-4" />
                                <span>Drag</span>
                            </span>
                        </div>
                        <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            @foreach ($stats as $stat)
                                <x-stat-card :label="$stat['label']" :value="$stat['value']" :description="$stat['description']" :icon="$stat['icon']" :source="$stat['source']" />
                            @endforeach
                        </div>
                    </div>
                </div>

                @foreach ($chartWidgets as $chart)
                    <div data-widget-key="{{ $chart['widget_key'] }}" :draggable="dragAllowed" @dragstart="startDrag('{{ $chart['widget_key'] }}')" @dragend="endDrag()" @dragover.prevent="dragAllowed" @drop="dropWidget('{{ $chart['widget_key'] }}')" x-show="isVisible('{{ $chart['widget_key'] }}')" x-cloak class="dashboard-widget is-half" :class="draggingKey === '{{ $chart['widget_key'] }}' ? 'is-dragging' : ''">
                        <div class="panel p-5 chart-panel" data-dashboard-chart>
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="section-kicker">{{ $chart['eyebrow'] }}</p>
                                    <h2 class="type-section-title mt-1" style="color: var(--text-primary);">{{ $chart['title'] }}</h2>
                                    <p class="type-body mt-2 text-muted">{{ $chart['description'] }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="selection-chip is-selected">{{ $chart['badge'] }}</span>
                                    <span class="dashboard-drag-handle" x-show="dragAllowed" x-cloak>
                                        <x-icon name="chart-bar" class="h-4 w-4" />
                                        <span>Drag</span>
                                    </span>
                                </div>
                            </div>
                            <div class="chart-shell mt-5">
                                <div class="chart-loader">
                                    <span class="loader-spinner"></span>
                                </div>
                                <canvas id="{{ $chart['canvas_id'] }}" height="260"></canvas>
                            </div>
                            @if ($chart['source_table'])
                                <p class="mt-3 text-[11px] font-semibold uppercase tracking-[0.16em] text-muted">{{ $chart['source_table'] }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach

                @if ($isTenantWorkspace)
                    @foreach ($tenantSections as $widgetKey => $section)
                        <div data-widget-key="{{ $widgetKey }}" :draggable="dragAllowed" @dragstart="startDrag('{{ $widgetKey }}')" @dragend="endDrag()" @dragover.prevent="dragAllowed" @drop="dropWidget('{{ $widgetKey }}')" x-show="isVisible('{{ $widgetKey }}')" x-cloak class="dashboard-widget is-wide" :class="draggingKey === '{{ $widgetKey }}' ? 'is-dragging' : ''">
                            <div class="panel p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="section-kicker">Tenant ERP</p>
                                        <h2 class="type-section-title mt-1" style="color: var(--text-primary);">{{ $section['title'] }}</h2>
                                        <p class="type-body mt-2 text-muted">{{ $section['description'] }}</p>
                                    </div>
                                    <span class="dashboard-drag-handle" x-show="dragAllowed" x-cloak>
                                        <x-icon name="layers" class="h-4 w-4" />
                                        <span>Drag</span>
                                    </span>
                                </div>

                                @if (! empty($section['cards']))
                                    <div class="mt-5 grid gap-4 md:grid-cols-3">
                                        @foreach ($section['cards'] as $card)
                                            <div class="surface-soft p-4">
                                                <p class="type-section-title" style="color: var(--text-primary);">{{ $card['value'] }}</p>
                                                <p class="type-card-title mt-2" style="color: var(--text-primary);">{{ $card['label'] }}</p>
                                                <p class="type-body mt-1 text-muted">{{ $card['description'] }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                @if (! empty($section['lists']))
                                    <div class="mt-5 grid gap-4 lg:grid-cols-2">
                                        @foreach ($section['lists'] as $list)
                                            <div class="surface-soft p-4">
                                                <p class="type-card-title" style="color: var(--text-primary);">{{ $list['title'] }}</p>
                                                <div class="mt-3 space-y-3">
                                                    @forelse ($list['items'] as $item)
                                                        <div class="rounded-2xl border px-3 py-3" style="border-color: var(--panel-border);">
                                                            <p class="text-sm font-semibold" style="color: var(--text-primary);">{{ $item['title'] }}</p>
                                                            <p class="mt-1 text-sm text-muted">{{ $item['meta'] }}</p>
                                                        </div>
                                                    @empty
                                                        <p class="type-body text-muted">No items need action right now.</p>
                                                    @endforelse
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @endif

                @role('Super Admin')
                    <div data-widget-key="recentActivity" :draggable="dragAllowed" @dragstart="startDrag('recentActivity')" @dragend="endDrag()" @dragover.prevent="dragAllowed" @drop="dropWidget('recentActivity')" x-show="isVisible('recentActivity')" x-cloak class="dashboard-widget is-wide" :class="draggingKey === 'recentActivity' ? 'is-dragging' : ''">
                        <div class="panel p-5">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="section-kicker">Recent activity</p>
                                    <h2 class="type-section-title mt-1" style="color: var(--text-primary);">Latest admin events</h2>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.activity.index') }}" class="btn-secondary icon-label-button">
                                        <x-icon name="arrow-right" class="h-4 w-4" />
                                        <span>Open log</span>
                                    </a>
                                    <span class="dashboard-drag-handle" x-show="dragAllowed" x-cloak>
                                        <x-icon name="activity" class="h-4 w-4" />
                                        <span>Drag</span>
                                    </span>
                                </div>
                            </div>
                            <div class="mt-5 space-y-3">
                                @forelse ($recentActivities as $activity)
                                    <div class="activity-item">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="selection-chip is-selected">{{ $subjectLabels[$activity->subject_type] ?? 'System' }}</span>
                                                    <span class="selection-chip">{{ str($activity->event ?: $activity->description ?: 'recorded')->headline() }}</span>
                                                </div>
                                                <p class="type-card-title mt-3" style="color: var(--text-primary);">{{ $activity->description ?: 'Activity recorded' }}</p>
                                                <p class="type-meta mt-1 text-muted">{{ $activity->causer?->name ?? 'System' }}</p>
                                            </div>
                                            <span class="type-meta text-muted sm:text-right">{{ $activity->created_at?->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                @empty
                                    <p class="type-body text-muted">No activity has been recorded yet.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endrole

                <div data-widget-key="quickActions" :draggable="dragAllowed" @dragstart="startDrag('quickActions')" @dragend="endDrag()" @dragover.prevent="dragAllowed" @drop="dropWidget('quickActions')" x-show="isVisible('quickActions')" x-cloak class="dashboard-widget is-side" :class="draggingKey === 'quickActions' ? 'is-dragging' : ''">
                    <div class="panel p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="section-kicker">Quick actions</p>
                                <h2 class="type-section-title mt-1" style="color: var(--text-primary);">{{ $isTenantWorkspace ? 'Run the tenant workspace' : 'Manage the platform' }}</h2>
                            </div>
                            <span class="dashboard-drag-handle" x-show="dragAllowed" x-cloak>
                                <x-icon name="settings" class="h-4 w-4" />
                                <span>Drag</span>
                            </span>
                        </div>
                        <div class="mt-5 grid gap-3">
                            @foreach ($quickActions as $action)
                                @if (! empty($action['form_action']))
                                <form method="POST" action="{{ $action['form_action'] }}" data-loading data-loading-message="{{ $action['loading_message'] ?? 'Working...' }}">
                                    @csrf
                                    <button type="submit" class="quick-action-card w-full text-left">
                                        <x-icon :name="$action['icon']" class="h-4 w-4" />
                                        <div>
                                            <p class="type-card-title" style="color: var(--text-primary);">{{ $action['title'] }}</p>
                                            <p class="type-body mt-1 text-muted">{{ $action['description'] }}</p>
                                        </div>
                                    </button>
                                </form>
                                @else
                                <a href="{{ $action['url'] }}" @if (! empty($action['modal_url'])) data-modal-url="{{ $action['modal_url'] }}" @endif class="quick-action-card" @if (! empty($action['loading_message'])) data-loading data-loading-message="{{ $action['loading_message'] }}" @endif>
                                    <x-icon :name="$action['icon']" class="h-4 w-4" />
                                    <div>
                                        <p class="type-card-title" style="color: var(--text-primary);">{{ $action['title'] }}</p>
                                        <p class="type-body mt-1 text-muted">{{ $action['description'] }}</p>
                                    </div>
                                </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>

                @if ($intelligencePreview)
                    <div data-widget-key="intelligence" :draggable="dragAllowed" @dragstart="startDrag('intelligence')" @dragend="endDrag()" @dragover.prevent="dragAllowed" @drop="dropWidget('intelligence')" x-show="isVisible('intelligence')" x-cloak class="dashboard-widget is-full" :class="draggingKey === 'intelligence' ? 'is-dragging' : ''">
                        <div class="panel p-5">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="section-kicker">Super Admin</p>
                                    <h2 class="type-section-title mt-1" style="color: var(--text-primary);">Intelligence layer preview</h2>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.intelligence.index') }}" class="btn-primary icon-label-button" data-loading data-loading-message="Opening intelligence layer...">
                                        <x-icon name="arrow-right" class="h-4 w-4" />
                                        <span>Open master page</span>
                                    </a>
                                    <span class="dashboard-drag-handle" x-show="dragAllowed" x-cloak>
                                        <x-icon name="sparkles" class="h-4 w-4" />
                                        <span>Drag</span>
                                    </span>
                                </div>
                            </div>

                            <div class="mt-5 grid gap-4 lg:grid-cols-3">
                                @foreach ($intelligencePreview['highlights'] as $highlight)
                                    <x-stat-card :label="$highlight['label']" :value="$highlight['value']" :description="$highlight['description']" class="p-5" />
                                @endforeach
                            </div>

                            <div class="mt-5 grid gap-3 lg:grid-cols-3">
                                @foreach ($intelligencePreview['recommendations'] as $recommendation)
                                    <div class="surface-soft type-body p-4" style="color: var(--text-primary);">{{ $recommendation }}</div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.initDashboardCharts({
                @foreach ($chartWidgets as $chart)
                    '{{ $chart['id'] }}': {
                        canvas: '#{{ $chart['canvas_id'] }}',
                        wrapper: '#{{ $chart['canvas_id'] }}',
                        type: @json($chart['type']),
                        labels: @json($chart['labels']),
                        datasets: [{
                            label: @json($chart['dataset_label']),
                            data: @json($chart['values']),
                        }],
                    },
                @endforeach
            });
        });
    </script>
</x-app-layout>