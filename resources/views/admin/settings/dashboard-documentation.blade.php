<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="type-page-title" style="color: var(--text-primary);">Dashboard Documentation</h1>
            <p class="type-body mt-1 text-muted">Short references for dashboard sources, chart types, icons, and auth visuals.</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="panel p-5 sm:p-6">
            <p class="section-kicker">Stat sources</p>
            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                @foreach ($statSources as $key => $meta)
                    <div class="theme-card">
                        <p class="type-card-title" style="color: var(--text-primary);">{{ $meta['label'] }}</p>
                        <p class="type-body mt-2 text-muted">{{ $meta['description'] }}</p>
                        <p class="mt-3 text-[11px] font-semibold uppercase tracking-[0.16em] text-muted">{{ $key }} • {{ $meta['table'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="panel p-5 sm:p-6">
            <p class="section-kicker">Chart sources</p>
            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                @foreach ($chartSources as $key => $meta)
                    <div class="theme-card">
                        <p class="type-card-title" style="color: var(--text-primary);">{{ $meta['label'] }}</p>
                        <p class="type-body mt-2 text-muted">{{ $meta['description'] }}</p>
                        <p class="mt-3 text-[11px] font-semibold uppercase tracking-[0.16em] text-muted">{{ $key }} • {{ $meta['table'] }}</p>
                        <p class="mt-2 text-sm text-muted">Types: {{ collect($meta['types'])->map(fn ($type) => $chartTypes[$type] ?? $type)->implode(', ') }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="panel p-5 sm:p-6">
                <p class="section-kicker">Icons</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach ($iconOptions as $key => $label)
                        <div class="quick-action-card">
                            <x-icon :name="$key" class="h-4 w-4" />
                            <div>
                                <p class="type-card-title" style="color: var(--text-primary);">{{ $label }}</p>
                                <p class="type-body mt-1 text-muted">{{ $key }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="panel p-5 sm:p-6">
                <p class="section-kicker">Auth visuals</p>
                <div class="mt-4 space-y-3 text-sm text-muted">
                    <p>Use `Default animation` to keep the built-in SVG scene.</p>
                    <p>Use `Custom image` to upload a login or register image from settings.</p>
                    <p>The uploaded image keeps a small floating animation automatically.</p>
                    <p>Recommended size: 1200×900 or larger.</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>