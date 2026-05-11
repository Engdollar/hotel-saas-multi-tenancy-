@forelse ($ticket->replies as $reply)
    @if (! $reply->is_internal || $isSuperAdmin)
        <article class="rounded-2xl border p-4" style="border-color: var(--panel-border); background: {{ $reply->is_internal ? 'rgba(245, 158, 11, 0.08)' : 'var(--panel-soft)' }};">
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-semibold" style="color: var(--text-primary);">{{ $reply->author_name ?? $reply->user?->name ?? 'Unknown user' }}</p>
                <div class="flex items-center gap-2">
                    @if ($reply->is_internal)
                        <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold uppercase" style="background: rgba(245, 158, 11, 0.15); color: rgba(245, 158, 11, 1);">Internal note</span>
                    @endif
                    <span class="text-xs text-muted">{{ $reply->created_at->diffForHumans() }}</span>
                </div>
            </div>
            <div class="ticket-rich-content mt-3 text-sm" style="color: var(--text-primary);">{!! $reply->body !!}</div>
        </article>
    @endif
@empty
    <p class="rounded-2xl border px-4 py-6 text-sm text-muted" style="border-color: var(--panel-border);">No replies yet.</p>
@endforelse
