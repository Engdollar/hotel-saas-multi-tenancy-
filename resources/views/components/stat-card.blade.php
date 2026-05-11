@props(['label', 'value', 'description', 'icon' => null, 'source' => null])

<div {{ $attributes->merge(['class' => 'panel p-6']) }}>
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="section-kicker">{{ $label }}</p>
            <p class="mt-3 text-2xl font-black" style="color: var(--text-primary);">{{ $value }}</p>
        </div>
        @if ($icon)
            <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border" style="border-color: color-mix(in srgb, var(--accent) 18%, var(--panel-border)); background: color-mix(in srgb, var(--accent) 12%, var(--panel-soft)); color: var(--accent);">
                <x-icon :name="$icon" class="h-5 w-5" />
            </span>
        @endif
    </div>
    <p class="mt-2 text-sm text-muted">{{ $description }}</p>
    @if ($source)
        <p class="mt-3 text-[11px] font-semibold uppercase tracking-[0.16em] text-muted">{{ $source }}</p>
    @endif
</div>