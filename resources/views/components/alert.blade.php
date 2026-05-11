@props(['type' => 'success', 'message'])

@php
    $styles = [
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/70 dark:bg-emerald-950/40 dark:text-emerald-300',
        'error' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/70 dark:bg-rose-950/40 dark:text-rose-300',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'panel border px-5 py-4 text-sm font-medium '.$styles[$type]]) }}>
    {{ $message }}
</div>