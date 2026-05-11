<x-guest-layout illustration="login">
    <div>
        <p class="text-sm font-bold uppercase tracking-[0.3em]" style="color: var(--accent);">Tenant access status</p>
        <h1 class="mt-3 text-3xl font-black sm:text-4xl" style="color: var(--text-primary);">{{ $statusContent['title'] }}</h1>
        <p class="mt-3 text-sm text-muted">{{ $statusContent['message'] }}</p>
    </div>

    <div class="surface-soft mt-8 rounded-[1.5rem] border p-5" style="border-color: color-mix(in srgb, var(--panel-border) 100%, transparent);">
        <div class="flex items-center justify-between gap-3">
            <p class="text-sm font-semibold" style="color: var(--text-primary);">{{ $company?->name ?? 'Unknown company' }}</p>
            <span class="selection-chip is-selected">{{ $statusContent['pill'] }}</span>
        </div>
        <p class="mt-3 text-sm text-muted">You can sign out safely while waiting. Once the Super Admin changes the tenancy status to active, your dashboard access will be restored.</p>

        <form method="POST" action="{{ route('logout') }}" class="mt-5">
            @csrf
            <button type="submit" class="btn-primary w-full">Sign out</button>
        </form>
    </div>
</x-guest-layout>