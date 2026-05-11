<div class="flex flex-wrap gap-2">
    @forelse ($user->roles as $role)
        <span class="badge bg-cyan-100 text-cyan-700 dark:bg-cyan-950/60 dark:text-cyan-300">{{ $role->name }}</span>
    @empty
        <span class="text-sm text-slate-500 dark:text-slate-400">No roles</span>
    @endforelse
</div>