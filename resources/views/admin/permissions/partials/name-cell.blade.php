<div>
    <p class="font-semibold text-slate-900 dark:text-white">{{ $permission->name }}</p>
    <p class="text-sm text-slate-500 dark:text-slate-400">{{ str($permission->name)->after('-')->headline() }} module</p>
</div>