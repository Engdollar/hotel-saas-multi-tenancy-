<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-black" style="color: var(--text-primary);">{{ $ticket->ticket_number }}</h1>
            <p class="mt-1 text-sm text-muted">{{ $ticket->subject }}</p>
        </div>
    </x-slot>

    <div class="grid gap-5 xl:grid-cols-[1.2fr_0.8fr]">
        <section class="panel p-5 sm:p-6">
            <div class="rounded-2xl border p-4" style="border-color: var(--panel-border); background: var(--panel-soft);">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-muted">Issue Description</p>
                <div class="ticket-rich-content mt-3 text-sm" style="color: var(--text-primary);">{!! $ticket->description !!}</div>
            </div>

            <div class="mt-6 space-y-4">
                <h2 class="text-lg font-black" style="color: var(--text-primary);">Conversation</h2>
                <div id="ticket-conversation-feed" class="space-y-4">
                    @include('admin.tickets.partials.conversation', ['ticket' => $ticket, 'isSuperAdmin' => $isSuperAdmin])
                </div>
            </div>

            <form method="POST" action="{{ route('admin.tickets.replies.store', $ticket) }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="body" class="text-sm font-semibold" style="color: var(--text-primary);">Add reply</label>
                    <textarea id="body" name="body" rows="5" required class="form-input mt-2">{{ old('body') }}</textarea>
                    <p class="mt-2 text-xs text-muted">You can paste screenshots directly (including WhatsApp images).</p>
                    <x-input-error :messages="$errors->get('body')" class="mt-2" />
                </div>

                @if ($isSuperAdmin)
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="is_internal" value="1" class="rounded border-slate-300">
                        <span class="text-sm text-muted">Internal note (hidden from tenant users)</span>
                    </label>
                @endif

                <div class="flex justify-end">
                    <button type="submit" class="btn-primary">Send reply</button>
                </div>
            </form>
        </section>

        <aside class="space-y-5">
            <section class="panel p-5">
                <h2 class="text-sm font-black uppercase tracking-[0.2em] text-muted">Ticket Control</h2>

                <form method="POST" action="{{ route('admin.tickets.update', $ticket) }}" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="status" class="text-sm font-semibold" style="color: var(--text-primary);">Status</label>
                        <select id="status" name="status" class="form-input mt-2" required>
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}" @selected(old('status', $ticket->status) === $status)>{{ str($status)->replace('_', ' ')->headline() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="priority" class="text-sm font-semibold" style="color: var(--text-primary);">Priority</label>
                        <select id="priority" name="priority" class="form-input mt-2" required>
                            @foreach ($priorities as $priority)
                                <option value="{{ $priority }}" @selected(old('priority', $ticket->priority) === $priority)>{{ str($priority)->headline() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="assigned_to_user_id" class="text-sm font-semibold" style="color: var(--text-primary);">Assign to</label>
                        <select id="assigned_to_user_id" name="assigned_to_user_id" class="form-input mt-2" @disabled(! $isSuperAdmin)>
                            <option value="">Unassigned</option>
                            @foreach ($assignedUsers as $assignee)
                                <option value="{{ $assignee->id }}" @selected((int) old('assigned_to_user_id', (int) $ticket->assigned_to_user_id) === (int) $assignee->id)>
                                    {{ $assignee->name }} ({{ $assignee->email }})
                                </option>
                            @endforeach
                        </select>
                        @if (! $isSuperAdmin)
                            <p class="mt-1 text-xs text-muted">Only super admins can reassign tickets.</p>
                        @endif
                    </div>

                    <button type="submit" class="btn-primary w-full">Update ticket</button>
                </form>
            </section>

            <section class="panel p-5">
                <h2 class="text-sm font-black uppercase tracking-[0.2em] text-muted">Details</h2>
                <div id="ticket-details-panel">
                    @include('admin.tickets.partials.details', ['ticket' => $ticket])
                </div>
            </section>
        </aside>
    </div>

    <style>
        .ticket-rich-content img {
            max-width: 100%;
            height: auto;
            border-radius: 0.85rem;
            margin-top: 0.5rem;
            margin-bottom: 0.5rem;
            border: 1px solid var(--panel-border);
        }

        .ticket-rich-content p + p {
            margin-top: 0.55rem;
        }
    </style>

    @php
        $ticketFingerprint = implode('|', [
            optional($ticket->updated_at)->timestamp,
            optional($ticket->last_reply_at)->timestamp,
            optional($ticket->replies->max('updated_at'))->timestamp,
            $ticket->replies->count(),
        ]);
    @endphp

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const bodyInput = document.querySelector('#body');
            const statusSelect = document.querySelector('#status');
            const prioritySelect = document.querySelector('#priority');
            const assigneeSelect = document.querySelector('#assigned_to_user_id');
            const feed = document.querySelector('#ticket-conversation-feed');
            const details = document.querySelector('#ticket-details-panel');

            const uploadUrl = @json(route('admin.tickets.editor.upload-image'));
            const streamUrl = @json(route('admin.tickets.stream', $ticket));
            let fingerprint = @json($ticketFingerprint);

            if (bodyInput && window.initializeSupportEditor) {
                window.initializeSupportEditor(bodyInput, {
                    uploadUrl,
                });
            }

            if (window.startTicketLiveStream) {
                window.startTicketLiveStream({
                    endpoint: streamUrl,
                    fingerprint,
                    onUpdate(payload) {
                        fingerprint = payload.fingerprint;

                        if (feed && typeof payload.conversation_html === 'string') {
                            feed.innerHTML = payload.conversation_html;
                        }

                        if (details && typeof payload.details_html === 'string') {
                            details.innerHTML = payload.details_html;
                        }

                        if (statusSelect && payload.status) {
                            statusSelect.value = payload.status;
                        }

                        if (prioritySelect && payload.priority) {
                            prioritySelect.value = payload.priority;
                        }

                        if (assigneeSelect) {
                            assigneeSelect.value = payload.assigned_to_user_id ?? '';
                        }

                        window.dispatchAppToast('success', 'New ticket updates received.');
                    },
                });
            }
        });
    </script>
</x-app-layout>
