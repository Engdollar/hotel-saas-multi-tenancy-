@php
    $hasOnlyLockedBootstrapRole = $user->roles->count() === 1
        && $user->roles->first()?->is_locked
        && $user->roles->first()?->company_id !== null;
@endphp

<div class="flex items-center gap-3">
    <x-avatar :user="$user" />
    <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2">
            <p class="font-semibold text-slate-900 dark:text-white">{{ $user->name }}</p>
            @if ($hasOnlyLockedBootstrapRole)
                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-bold uppercase tracking-[0.12em] text-amber-700 dark:bg-amber-950/60 dark:text-amber-300">Protected</span>
            @endif
        </div>
        <p class="text-sm text-slate-500 dark:text-slate-400">{{ $user->email }}</p>
    </div>
</div>