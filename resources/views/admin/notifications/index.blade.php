<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-black" style="color: var(--text-primary);">Notifications</h1>
            <p class="mt-1 text-sm text-muted">Read system updates, filter what still needs action, and clear alerts as they are handled.</p>
        </div>
    </x-slot>

    <div class="space-y-5">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="panel p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-muted">Total</p>
                <p class="mt-2 text-2xl font-black" style="color: var(--text-primary);">{{ $stats['total'] }}</p>
            </div>
            <div class="panel p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-muted">Unread</p>
                <p class="mt-2 text-2xl font-black" style="color: var(--text-primary);">{{ $stats['unread'] }}</p>
            </div>
            <div class="panel p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-muted">Read</p>
                <p class="mt-2 text-2xl font-black" style="color: var(--text-primary);">{{ $stats['read'] }}</p>
            </div>
            <div class="panel p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-muted">Actionable</p>
                <p class="mt-2 text-2xl font-black" style="color: var(--text-primary);">{{ $stats['actionable'] }}</p>
            </div>
        </div>

        <div class="panel p-6">
            <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <form method="GET" action="{{ route('admin.notifications.index') }}" class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_12rem_auto] xl:flex-1">
                    <input type="search" name="search" value="{{ $filters['search'] }}" class="form-input" placeholder="Search title, message, or type">
                    <select name="read_state" class="form-input">
                        <option value="all" @selected($filters['read_state'] === 'all')>All notifications</option>
                        <option value="unread" @selected($filters['read_state'] === 'unread')>Unread only</option>
                        <option value="read" @selected($filters['read_state'] === 'read')>Read only</option>
                    </select>
                    <div class="flex gap-2">
                        <button type="submit" class="btn-secondary">Filter</button>
                        <a href="{{ route('admin.notifications.index') }}" class="btn-secondary">Reset</a>
                    </div>
                </form>

                <div class="flex items-center gap-2">
                    <span class="selection-chip is-selected">{{ $notifications->total() }} matching</span>
                    <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                        @csrf
                        <button type="submit" class="btn-secondary">Mark all as read</button>
                    </form>
                </div>
            </div>

            <div>
                <p class="text-xs font-bold uppercase tracking-[0.3em] text-cyan-500">Inbox</p>
                <h2 class="mt-2 text-xl font-black text-slate-900 dark:text-white">All notifications</h2>
            </div>

            <div class="space-y-4">
                @forelse ($notifications as $notification)
                    <div class="rounded-3xl border border-slate-200/70 p-5 dark:border-slate-800 {{ $notification->read_at ? 'opacity-75' : '' }}">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="selection-chip {{ $notification->read_at ? '' : 'is-selected' }}">{{ $notification->read_at ? 'Read' : 'Unread' }}</span>
                                    <span class="selection-chip">{{ str(class_basename($notification->type))->headline() }}</span>
                                </div>
                                <p class="mt-3 text-base font-bold text-slate-900 dark:text-white">{{ $notification->data['title'] ?? 'System update' }}</p>
                                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ $notification->data['message'] ?? '' }}</p>
                                @if (! empty($notification->data['url']))
                                    <a href="{{ $notification->data['url'] }}" class="mt-3 inline-flex text-sm font-semibold text-cyan-500">Open related page</a>
                                @endif
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-slate-400">{{ $notification->created_at?->diffForHumans() }}</p>
                                @if (! $notification->read_at)
                                    <form method="POST" action="{{ route('admin.notifications.read', $notification) }}" class="mt-3">
                                        @csrf
                                        <button type="submit" class="text-sm font-semibold text-cyan-500">Mark as read</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 dark:text-slate-400">No notifications matched the current filters.</p>
                @endforelse
            </div>

            <div class="mt-6">
                {{ $notifications->links() }}
            </div>
        </div>
    </div>
</x-app-layout>