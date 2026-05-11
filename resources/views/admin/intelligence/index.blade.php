<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-black" style="color: var(--text-primary);">Dashboard Intelligence</h1>
            <p class="mt-1 text-sm text-muted">Super admin master page for risk signals, coverage gaps, and reporting intelligence.</p>
        </div>
    </x-slot>

    <div class="grid gap-4 lg:grid-cols-4">
        @foreach ($highlights as $highlight)
            <x-stat-card :label="$highlight['label']" :value="$highlight['value']" :description="$highlight['description']" />
        @endforeach
    </div>

    <div class="grid gap-5 xl:grid-cols-[1.1fr_0.9fr]">
        <div class="space-y-5">
            <div class="grid gap-5 lg:grid-cols-2">
                <div class="panel p-5 chart-panel">
                    <p class="section-kicker">Signal Trend</p>
                    <h2 class="mt-1 text-lg font-black" style="color: var(--text-primary);">Platform activity</h2>
                    <div class="chart-shell mt-5">
                        <div class="chart-loader"><span class="loader-spinner"></span></div>
                        <canvas id="intelligence-activity-chart" height="250"></canvas>
                    </div>
                </div>

                <div class="panel p-5 chart-panel">
                    <p class="section-kicker">Role Spread</p>
                    <h2 class="mt-1 text-lg font-black" style="color: var(--text-primary);">Current role distribution</h2>
                    <div class="chart-shell mt-5">
                        <div class="chart-loader"><span class="loader-spinner"></span></div>
                        <canvas id="intelligence-role-chart" height="250"></canvas>
                    </div>
                </div>
            </div>

            <div class="panel p-5">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="section-kicker">Recommendations</p>
                        <h2 class="mt-1 text-lg font-black" style="color: var(--text-primary);">Immediate focus areas</h2>
                    </div>
                    <a href="{{ route('admin.reports.index') }}" class="btn-secondary icon-label-button" data-loading data-loading-message="Opening reports...">
                        <x-icon name="arrow-right" class="h-4 w-4" />
                        <span>Open reports</span>
                    </a>
                </div>
                <div class="mt-5 grid gap-3">
                    @foreach ($recommendations as $recommendation)
                        <div class="surface-soft p-4 text-sm" style="color: var(--text-primary);">{{ $recommendation }}</div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="space-y-5">
            <div class="panel p-5">
                <p class="section-kicker">Role Risk</p>
                <h2 class="mt-1 text-lg font-black" style="color: var(--text-primary);">Roles needing review</h2>
                <div class="mt-5 space-y-3">
                    @foreach ($roleRisk as $role)
                        <div class="activity-item">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold" style="color: var(--text-primary);">{{ $role->name }}</p>
                                    <p class="mt-1 text-xs text-muted">{{ $role->permissions_count }} permissions • {{ $role->users_count }} users</p>
                                </div>
                                <span class="selection-chip is-selected">{{ $role->permissions_count + ($role->users_count * 2) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="panel p-5">
                <p class="section-kicker">Latest Signals</p>
                <h2 class="mt-1 text-lg font-black" style="color: var(--text-primary);">Recent activity</h2>
                <div class="mt-5 space-y-3">
                    @foreach ($recentActivities as $activity)
                        <div class="activity-item">
                            <p class="text-sm font-semibold" style="color: var(--text-primary);">{{ $activity->description ?: 'Activity recorded' }}</p>
                            <p class="mt-1 text-xs text-muted">{{ $activity->created_at?->diffForHumans() }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.initDashboardCharts({
                intelligenceActivity: {
                    canvas: '#intelligence-activity-chart',
                    wrapper: '#intelligence-activity-chart',
                    type: 'line',
                    labels: @json($activityTrend['labels']),
                    datasets: [{ label: 'Events', data: @json($activityTrend['values']) }],
                },
                intelligenceRoles: {
                    canvas: '#intelligence-role-chart',
                    wrapper: '#intelligence-role-chart',
                    type: 'pie',
                    labels: @json($roleDistribution['labels']),
                    datasets: [{ label: 'Users', data: @json($roleDistribution['values']) }],
                },
            });
        });
    </script>
</x-app-layout>