@props(['fullWidth' => false, 'compact' => false])

<div class="relative" x-data="{ open: false }" @click.outside="open = false">
    @if ($compact)
        <button type="button" @click="open = !open" class="user-menu-avatar" :aria-expanded="open.toString()" aria-label="Open user menu">
            <x-avatar :user="auth()->user()" size="h-8 w-8 rounded-full" textSize="text-[10px]" />
        </button>
    @else
        <button type="button" @click="open = !open" class="user-menu-trigger {{ $fullWidth ? 'w-full justify-between' : '' }}" :aria-expanded="open.toString()">
            <span class="flex min-w-0 items-center gap-3">
                <x-avatar :user="auth()->user()" />
                <span class="min-w-0 text-left">
                    <span class="block truncate text-sm font-semibold">{{ auth()->user()->name }}</span>
                    <span class="block truncate text-xs text-muted">{{ auth()->user()->email }}</span>
                </span>
            </span>
            <x-icon name="chevron-down" class="h-4 w-4 shrink-0 transition" ::class="open ? 'rotate-180' : ''" />
        </button>
    @endif

    <div x-show="open" x-cloak x-transition class="user-menu-panel {{ $fullWidth ? 'left-0 right-0 w-full' : 'right-0 w-[240px] sm:w-[260px]' }}">
        <a href="{{ route('profile.edit') }}" class="user-menu-link">
            <x-icon name="user" class="h-4 w-4" />
            <span>My Profile</span>
        </a>

        @role('Super Admin')
            <a href="{{ route('admin.notifications.index') }}" class="user-menu-link">
                <x-icon name="bell" class="h-4 w-4" />
                <span>Notifications</span>
            </a>
        @endrole

        @role('Super Admin')
            <a href="{{ route('admin.settings.index') }}" class="user-menu-link">
                <x-icon name="settings" class="h-4 w-4" />
                <span>Account Settings</span>
            </a>
        @endrole

        <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t pt-1" style="border-color: var(--panel-border);">
            @csrf
            <button type="submit" class="user-menu-link w-full">
                <x-icon name="logout" class="h-4 w-4" />
                <span>Sign Out</span>
            </button>
        </form>
    </div>
</div>