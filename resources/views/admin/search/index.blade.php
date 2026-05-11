<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="type-page-title" style="color: var(--text-primary);">Search</h1>
            <p class="type-body mt-1 text-muted">Find records across users, roles, permissions, notifications, activity, and settings.</p>
        </div>
    </x-slot>

    @if ($query !== '')
        <div class="grid gap-4 lg:grid-cols-3">
            <div class="panel p-5">
                <p class="section-kicker">Query</p>
                <h2 class="type-section-title mt-2" style="color: var(--text-primary);">{{ $query }}</h2>
                <p class="type-body mt-2 text-muted">Current keyword across the admin workspace.</p>
            </div>
            <div class="panel p-5">
                <p class="section-kicker">Results</p>
                <h2 class="type-section-title mt-2" style="color: var(--text-primary);">{{ $resultCount }}</h2>
                <p class="type-body mt-2 text-muted">Matches found for your query.</p>
            </div>
            <div class="panel p-5">
                <p class="section-kicker">Sources</p>
                <h2 class="type-section-title mt-2" style="color: var(--text-primary);">{{ count($groups) }}</h2>
                <p class="type-body mt-2 text-muted">Sections containing matching items.</p>
            </div>
        </div>
    @endif

    @if (! empty($restrictedDomains))
        <div class="notice-card">
            <p class="section-kicker">Restricted results</p>
            <p class="type-card-title mt-2">Some requested sections were intentionally hidden.</p>
            <p class="type-body mt-2 text-muted">This search mentioned {{ implode(', ', $restrictedDomains) }}, but those results are only shown when your account has access to them.</p>
        </div>
    @endif

    @if ($query === '')
        <div class="panel p-6">
            <p class="section-kicker">Search</p>
            <h2 class="type-section-title mt-2" style="color: var(--text-primary);">Start typing in the header search</h2>
            <p class="type-body mt-3 max-w-2xl text-muted">Use the header search to look through users, roles, permissions, notifications, activity logs, and settings you can access.</p>
        </div>
    @elseif ($resultCount === 0)
        <div class="panel p-6">
            <p class="section-kicker">No matches</p>
            <h2 class="type-section-title mt-2" style="color: var(--text-primary);">Nothing matched “{{ $query }}”</h2>
            <p class="type-body mt-3 max-w-2xl text-muted">Try a broader keyword or search by a specific name, email, role, permission, or activity event.</p>
        </div>
    @else
        <div class="grid gap-5 xl:grid-cols-2">
            @foreach ($groups as $group)
                <section class="panel p-5">
                    <div class="flex items-center gap-3">
                        <span class="icon-button is-accent">
                            <x-icon :name="$group['icon']" class="h-4 w-4" />
                        </span>
                        <div>
                            <p class="section-kicker">{{ $group['title'] }}</p>
                            <h2 class="type-section-title mt-1" style="color: var(--text-primary);">{{ count($group['items']) }} matches</h2>
                        </div>
                    </div>

                    <div class="mt-5 space-y-3">
                        @foreach ($group['items'] as $item)
                            <a href="{{ $item['url'] }}" class="activity-item block transition hover:-translate-y-[1px]">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="min-w-0 flex-1">
                                        <p class="type-card-title" style="color: var(--text-primary);">{{ $item['title'] }}</p>
                                        <p class="type-body mt-1 text-muted">{{ $item['description'] }}</p>
                                    </div>
                                    <span class="selection-chip">{{ $item['meta'] }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @endif
</x-app-layout>