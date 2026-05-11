<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-3xl font-black text-slate-900 dark:text-white">{{ $role->name }}</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Role details, assigned permissions, and linked users.</p>
        </div>
    </x-slot>

    <x-slot name="headerActions">
        <div class="flex items-center gap-2">
            @can('edit-role')
                <a href="{{ route('admin.roles.edit', $role) }}" data-modal-url="{{ route('admin.roles.edit', $role) }}" class="btn-primary">Edit role</a>
            @endcan
            <a href="{{ route('admin.roles.index') }}" class="btn-secondary">Back</a>
        </div>
    </x-slot>

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="panel p-6">
            <p class="text-xs font-bold uppercase tracking-[0.3em] text-cyan-500">Permissions</p>
            <div class="mt-4 flex flex-wrap gap-2">
                @forelse ($role->permissions as $permission)
                    <span class="badge bg-cyan-100 text-cyan-700 dark:bg-cyan-950/60 dark:text-cyan-300">{{ $permission->name }}</span>
                @empty
                    <span class="text-sm text-slate-500 dark:text-slate-400">No permissions assigned.</span>
                @endforelse
            </div>
        </div>

        <div class="panel p-6">
            <p class="text-xs font-bold uppercase tracking-[0.3em] text-cyan-500">Users</p>
            <div class="mt-4 space-y-3">
                @forelse ($role->users as $user)
                    <div class="flex items-center gap-3 rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
                        <x-avatar :user="$user" />
                        <div>
                            <p class="font-semibold text-slate-900 dark:text-white">{{ $user->name }}</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400">{{ $user->email }}</p>
                        </div>
                    </div>
                @empty
                    <span class="text-sm text-slate-500 dark:text-slate-400">No users assigned.</span>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>