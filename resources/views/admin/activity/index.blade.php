<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-black" style="color: var(--text-primary);">Activity Report</h1>
            <p class="mt-1 text-sm text-muted">Filter audit logs by actor, time window, action, and module.</p>
        </div>
    </x-slot>

    <div class="grid gap-4 lg:grid-cols-4">
        @foreach ($summary as $card)
            <x-stat-card :label="$card['label']" :value="$card['value']" :description="$card['description']" />
        @endforeach
    </div>

    <div class="grid gap-5 xl:grid-cols-[0.88fr_1.12fr]">
        <form method="GET" action="{{ route('admin.activity.index') }}" class="panel p-5">
            <div class="flex items-center gap-3">
                <span class="icon-button is-accent">
                    <x-icon name="filter" class="h-4 w-4" />
                </span>
                <div>
                    <p class="section-kicker">Filters</p>
                    <h2 class="mt-1 text-lg font-black" style="color: var(--text-primary);">Narrow the report</h2>
                </div>
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="causer_id" class="text-sm font-semibold" style="color: var(--text-primary);">User</label>
                    <select id="causer_id" name="causer_id" class="form-input">
                        <option value="">All users</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected(request('causer_id') == $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="subject_type" class="text-sm font-semibold" style="color: var(--text-primary);">Module</label>
                    <select id="subject_type" name="subject_type" class="form-input">
                        <option value="">All modules</option>
                        @foreach ($subjectOptions as $type => $label)
                            <option value="{{ $type }}" @selected(request('subject_type') === $type)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="event" class="text-sm font-semibold" style="color: var(--text-primary);">Activity</label>
                    <select id="event" name="event" class="form-input">
                        <option value="">All activities</option>
                        @foreach ($activityEvents as $event)
                            <option value="{{ $event }}" @selected(request('event') === $event)>{{ str($event)->headline() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="date_from" class="text-sm font-semibold" style="color: var(--text-primary);">From</label>
                    <input id="date_from" name="date_from" type="date" value="{{ request('date_from') }}" class="form-input">
                </div>
                <div>
                    <label for="date_to" class="text-sm font-semibold" style="color: var(--text-primary);">To</label>
                    <input id="date_to" name="date_to" type="date" value="{{ request('date_to') }}" class="form-input">
                </div>
            </div>

            <div class="mt-5 flex flex-wrap items-center gap-3">
                <button type="submit" class="btn-primary icon-label-button">
                    <x-icon name="sparkles" class="h-4 w-4" />
                    <span>Apply filters</span>
                </button>
                <a href="{{ route('admin.activity.index') }}" class="btn-secondary icon-label-button">
                    <x-icon name="refresh" class="h-4 w-4" />
                    <span>Reset</span>
                </a>
            </div>
        </form>

        <div class="panel p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                <div>
                    <p class="section-kicker">Audit Trail</p>
                    <h2 class="mt-1 text-lg font-black" style="color: var(--text-primary);">Recent matching events</h2>
                </div>
                <span class="selection-chip is-selected">{{ $activities->total() }} records</span>
            </div>

            <div class="mt-5 space-y-3">
                @forelse ($activities as $activity)
                    <article class="activity-item">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="selection-chip is-selected">{{ $subjectOptions[$activity->subject_type] ?? 'System' }}</span>
                                    <span class="selection-chip">{{ str($activity->event ?: $activity->description ?: 'recorded')->headline() }}</span>
                                </div>
                                <p class="mt-3 text-sm font-semibold" style="color: var(--text-primary);">{{ $activity->description ?: 'Activity recorded' }}</p>
                                <p class="mt-1 text-xs text-muted">{{ $activity->causer?->name ?? 'System' }} @if ($activity->subject_id) • Record #{{ $activity->subject_id }} @endif</p>
                            </div>
                            <span class="text-xs text-muted sm:whitespace-nowrap">{{ $activity->created_at?->format('M d, Y h:i A') }}</span>
                        </div>
                    </article>
                @empty
                    <p class="text-sm text-muted">No activity matched these filters.</p>
                @endforelse
            </div>

            <div class="mt-5">
                {{ $activities->links() }}
            </div>
        </div>
    </div>
</x-app-layout>