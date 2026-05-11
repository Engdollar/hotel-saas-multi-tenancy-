<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="type-page-title" style="color: var(--text-primary);">Reports</h1>
            <p class="type-body mt-1 text-muted">Date-filtered reporting with Excel and PDF exports for audit-heavy admin work.</p>
        </div>
    </x-slot>

    <div class="grid gap-4 lg:grid-cols-4">
        @foreach ($summary as $card)
            <x-stat-card :label="$card['label']" :value="$card['value']" :description="$card['description']" />
        @endforeach
    </div>

    <div class="grid gap-5 xl:grid-cols-[0.8fr_1.2fr]">
        <form method="GET" action="{{ route('admin.reports.index') }}" class="panel p-5">
            <div class="flex items-center gap-3">
                <span class="icon-button is-accent">
                    <x-icon name="filter" class="h-4 w-4" />
                </span>
                <div>
                    <p class="section-kicker">Reporting Filters</p>
                    <h2 class="type-section-title mt-1" style="color: var(--text-primary);">Filter by date</h2>
                </div>
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="date_from" class="type-body font-semibold" style="color: var(--text-primary);">From</label>
                    <input id="date_from" name="date_from" type="date" value="{{ $filters['date_from']->format('Y-m-d') }}" class="form-input">
                </div>
                <div>
                    <label for="date_to" class="type-body font-semibold" style="color: var(--text-primary);">To</label>
                    <input id="date_to" name="date_to" type="date" value="{{ $filters['date_to']->format('Y-m-d') }}" class="form-input">
                </div>
            </div>

            <div class="mt-5 flex flex-wrap gap-3">
                <button type="submit" class="btn-primary icon-label-button">
                    <x-icon name="sparkles" class="h-4 w-4" />
                    <span>Apply</span>
                </button>
                <a href="{{ route('admin.reports.index') }}" class="btn-secondary icon-label-button">
                    <x-icon name="refresh" class="h-4 w-4" />
                    <span>Reset</span>
                </a>
            </div>

            <div class="mt-5 border-t pt-5" style="border-color: var(--panel-border);">
                <p class="section-kicker">Exports</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="{{ route('admin.reports.export', ['format' => 'xlsx', 'date_from' => $filters['date_from']->format('Y-m-d'), 'date_to' => $filters['date_to']->format('Y-m-d')]) }}" class="btn-secondary" data-download data-download-filename="reports.xlsx" data-loading-message="Exporting reports to Excel...">Export Excel</a>
                    <a href="{{ route('admin.reports.export', ['format' => 'pdf', 'date_from' => $filters['date_from']->format('Y-m-d'), 'date_to' => $filters['date_to']->format('Y-m-d')]) }}" class="btn-secondary" data-download data-download-filename="reports.pdf" data-loading-message="Exporting reports to PDF...">Export PDF</a>
                </div>
            </div>
        </form>

        <div class="grid gap-5">
            <div class="panel p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="section-kicker">Module Reports</p>
                        <h2 class="type-section-title mt-1" style="color: var(--text-primary);">Activity grouped by module</h2>
                    </div>
                    <span class="selection-chip is-selected">{{ $moduleBreakdown->count() }} modules</span>
                </div>
                <div class="mt-5 grid gap-3 lg:grid-cols-2 xl:grid-cols-3">
                    @foreach ($moduleBreakdown as $module)
                        <div class="module-report-card">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="type-card-title" style="color: var(--text-primary);">{{ $module['module'] }}</p>
                                    <p class="type-body mt-1 text-muted">{{ $module['count'] }} records in the selected range.</p>
                                </div>
                                <span class="selection-chip">{{ $module['count'] }}</span>
                            </div>
                            <p class="type-meta mt-3 text-muted">Latest: {{ $module['latest_event'] }} • {{ $module['latest_at'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="grid gap-5 lg:grid-cols-2">
                <div class="panel p-5 chart-panel">
                    <div>
                        <p class="section-kicker">Line Chart</p>
                        <h2 class="type-section-title mt-1" style="color: var(--text-primary);">Activity over time</h2>
                    </div>
                    <div class="chart-shell mt-5">
                        <div class="chart-loader"><span class="loader-spinner"></span></div>
                        <canvas id="reports-activity-chart" height="250"></canvas>
                    </div>
                </div>

                <div class="panel p-5 chart-panel">
                    <div>
                        <p class="section-kicker">Pie Chart</p>
                        <h2 class="type-section-title mt-1" style="color: var(--text-primary);">Roles distribution</h2>
                    </div>
                    <div class="chart-shell mt-5">
                        <div class="chart-loader"><span class="loader-spinner"></span></div>
                        <canvas id="reports-role-chart" height="250"></canvas>
                    </div>
                </div>
            </div>

            <div class="panel p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="section-kicker">Report Stream</p>
                        <h2 class="type-section-title mt-1" style="color: var(--text-primary);">Filtered activity log by module</h2>
                    </div>
                    <span class="selection-chip is-selected">{{ $activities->total() }} records</span>
                </div>

                <div class="mt-5 space-y-3">
                    @php
                        $groupedActivities = $activities->getCollection()->groupBy(fn ($activity) => app(\App\Services\ReportsService::class)->moduleName($activity));
                    @endphp
                    @forelse ($groupedActivities as $module => $moduleActivities)
                        <section class="space-y-3">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="type-card-title" style="color: var(--text-primary);">{{ $module }}</h3>
                                <span class="selection-chip">{{ $moduleActivities->count() }} on this page</span>
                            </div>
                            @foreach ($moduleActivities as $activity)
                                <article class="activity-item">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="min-w-0 flex-1">
                                            <p class="type-card-title" style="color: var(--text-primary);">{{ $activity->description ?: 'Activity recorded' }}</p>
                                            <p class="type-meta mt-1 text-muted">{{ $activity->causer?->name ?? 'System' }} • {{ str($activity->event ?: 'recorded')->headline() }}</p>
                                        </div>
                                        <span class="type-meta text-muted">{{ $activity->created_at?->format('M d, Y h:i A') }}</span>
                                    </div>
                                </article>
                            @endforeach
                        </section>
                    @empty
                        <p class="type-body text-muted">No report data matched the selected dates.</p>
                    @endforelse
                </div>

                <div class="mt-5">
                    {{ $activities->links() }}
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.initDashboardCharts({
                reportsActivity: {
                    canvas: '#reports-activity-chart',
                    wrapper: '#reports-activity-chart',
                    type: 'line',
                    labels: @json($trend['labels']),
                    datasets: [{ label: 'Events', data: @json($trend['values']) }],
                },
                reportsRoles: {
                    canvas: '#reports-role-chart',
                    wrapper: '#reports-role-chart',
                    type: 'pie',
                    labels: @json($roleDistribution['labels']),
                    datasets: [{ label: 'Users', data: @json($roleDistribution['values']) }],
                },
            });
        });
    </script>
</x-app-layout>