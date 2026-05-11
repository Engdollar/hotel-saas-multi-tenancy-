<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-3xl font-black text-slate-900 dark:text-white">Dashboard</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">System overview and recent admin activity.</p>
        </div>
    </x-slot>

    <div class="grid gap-6 lg:grid-cols-4">
        <x-stat-card label="Welcome" :value="auth()->user()->name" description="Your authenticated admin session is active." />
        <x-stat-card label="Profile" :value="auth()->user()->roles->pluck('name')->implode(', ') ?: 'No role assigned'" description="Role assignments on this account." />
        <x-stat-card label="Notifications" :value="auth()->user()->unreadNotifications()->count()" description="Unread system notifications." />
        <x-stat-card label="Security" value="Verified" description="Email verification and password management available." />
    </div>

    <div class="panel p-6">
        <p class="text-sm text-slate-500 dark:text-slate-400">Use the sidebar to manage users, roles, permissions, branding settings, and your own account profile.</p>
    </div>
</x-app-layout>
