<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-3xl font-black text-slate-900 dark:text-white">{{ $permission->name }}</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Permission details and related roles.</p>
        </div>
    </x-slot>

    <x-slot name="headerActions">
        <div class="flex items-center gap-2">
            @can('edit-permission')
                <a href="{{ route('admin.permissions.edit', $permission) }}" data-modal-url="{{ route('admin.permissions.edit', $permission) }}" class="btn-primary">Edit permission</a>
            @endcan
            <a href="{{ route('admin.permissions.index') }}" class="btn-secondary">Back</a>
        </div>
    </x-slot>

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="panel p-6">
            <p class="text-xs font-bold uppercase tracking-[0.3em] text-cyan-500">Permission</p>
            <h2 class="mt-2 text-xl font-black text-slate-900 dark:text-white">{{ $permission->name }}</h2>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Guard: {{ $permission->guard_name }}</p>
        </div>

        <div class="panel p-6">
            <p class="text-xs font-bold uppercase tracking-[0.3em] text-cyan-500">Assigned roles</p>
            <div class="mt-4 flex flex-wrap gap-2">
                @forelse ($permission->roles as $role)
                    <span class="badge bg-cyan-100 text-cyan-700 dark:bg-cyan-950/60 dark:text-cyan-300">{{ $role->name }}</span>
                @empty
                    <span class="text-sm text-slate-500 dark:text-slate-400">No roles currently use this permission.</span>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>