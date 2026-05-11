<dl class="mt-4 space-y-3 text-sm">
    <div class="flex justify-between gap-4">
        <dt class="text-muted">Opened by</dt>
        <dd style="color: var(--text-primary);">{{ $ticket->creator?->name ?? 'Unknown' }}</dd>
    </div>
    <div class="flex justify-between gap-4">
        <dt class="text-muted">Category</dt>
        <dd style="color: var(--text-primary);">{{ str($ticket->category)->headline() }}</dd>
    </div>
    <div class="flex justify-between gap-4">
        <dt class="text-muted">Created</dt>
        <dd style="color: var(--text-primary);">{{ $ticket->created_at->format('M d, Y h:i A') }}</dd>
    </div>
    <div class="flex justify-between gap-4">
        <dt class="text-muted">Last reply</dt>
        <dd style="color: var(--text-primary);">{{ optional($ticket->last_reply_at)->diffForHumans() ?? 'N/A' }}</dd>
    </div>
    <div class="flex justify-between gap-4">
        <dt class="text-muted">First response</dt>
        <dd style="color: var(--text-primary);">{{ optional($ticket->first_response_at)->diffForHumans() ?? 'Pending' }}</dd>
    </div>
    <div class="flex justify-between gap-4">
        <dt class="text-muted">Resolved</dt>
        <dd style="color: var(--text-primary);">{{ optional($ticket->resolved_at)->diffForHumans() ?? 'No' }}</dd>
    </div>
</dl>
