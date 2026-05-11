<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-3xl font-black text-slate-900 dark:text-white">{{ $user->name }}</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">User details, assigned roles, and related activity.</p>
        </div>
    </x-slot>

    <div class="grid gap-6 xl:grid-cols-[0.85fr_1.15fr]">
        <div class="panel p-6">
            <div class="flex items-center gap-4">
                <x-avatar :user="$user" />
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white">{{ $user->name }}</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ $user->email }}</p>
                </div>
            </div>
            <div class="mt-6">
                <p class="text-xs font-bold uppercase tracking-[0.3em] text-cyan-500">Roles</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @forelse ($user->roles as $role)
                        <span class="badge bg-cyan-100 text-cyan-700 dark:bg-cyan-950/60 dark:text-cyan-300">{{ $role->name }}</span>
                    @empty
                        <span class="text-sm text-slate-500 dark:text-slate-400">No roles assigned.</span>
                    @endforelse
                </div>
            </div>
            <div class="mt-8 flex gap-3">
                @can('edit-user')
                    <a href="{{ route('admin.users.edit', $user) }}" data-modal-url="{{ route('admin.users.edit', $user) }}" class="btn-primary">Edit user</a>
                @endcan
                <a href="{{ route('admin.users.index') }}" class="btn-secondary">Back</a>
            </div>
        </div>

        <div class="panel p-6">
            <p class="text-xs font-bold uppercase tracking-[0.3em] text-cyan-500">Recent activity</p>
            <h2 class="mt-2 text-xl font-black text-slate-900 dark:text-white">Audit trail</h2>
            <div class="mt-6 space-y-4">
                @forelse ($activities as $activity)
                    <div class="rounded-2xl border border-slate-200/70 p-4 dark:border-slate-800">
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $activity->description ?: 'Activity recorded' }}</p>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $activity->created_at?->diffForHumans() }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 dark:text-slate-400">No activity entries for this user yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>