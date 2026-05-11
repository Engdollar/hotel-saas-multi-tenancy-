<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-black" style="color: var(--text-primary);">Support Tickets</h1>
            <p class="mt-1 text-sm text-muted">Track tenant support issues, prioritize responses, and resolve requests.</p>
        </div>
    </x-slot>

    <div class="space-y-5">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="panel p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-muted">Open</p>
                <p class="mt-2 text-2xl font-black" style="color: var(--text-primary);">{{ $stats['open'] }}</p>
            </div>
            <div class="panel p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-muted">In Progress</p>
                <p class="mt-2 text-2xl font-black" style="color: var(--text-primary);">{{ $stats['in_progress'] }}</p>
            </div>
            <div class="panel p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-muted">Waiting on Customer</p>
                <p class="mt-2 text-2xl font-black" style="color: var(--text-primary);">{{ $stats['waiting_on_customer'] }}</p>
            </div>
            <div class="panel p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-muted">Resolved/Closed</p>
                <p class="mt-2 text-2xl font-black" style="color: var(--text-primary);">{{ $stats['resolved'] }}</p>
            </div>
            <div class="panel p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-muted">Urgent</p>
                <p class="mt-2 text-2xl font-black" style="color: var(--text-primary);">{{ $stats['urgent'] }}</p>
            </div>
            <div class="panel p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-muted">Unassigned</p>
                <p class="mt-2 text-2xl font-black" style="color: var(--text-primary);">{{ $stats['unassigned'] }}</p>
            </div>
        </div>

        <div class="panel p-5">
            <form method="GET" action="{{ route('admin.tickets.index') }}" class="grid gap-3 lg:grid-cols-2 xl:grid-cols-[minmax(0,1.4fr)_repeat(5,minmax(0,12rem))_auto]">
                <input type="search" name="search" value="{{ $search }}" class="form-input" placeholder="Search by ticket number, subject, or category">
                <select name="status" class="form-input">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $statusOption)
                        <option value="{{ $statusOption }}" @selected($status === $statusOption)>{{ str($statusOption)->replace('_', ' ')->headline() }}</option>
                    @endforeach
                </select>
                <select name="priority" class="form-input">
                    <option value="">All priorities</option>
                    @foreach ($priorities as $priorityOption)
                        <option value="{{ $priorityOption }}" @selected($priority === $priorityOption)>{{ str($priorityOption)->headline() }}</option>
                    @endforeach
                </select>
                <select name="category" class="form-input">
                    <option value="">All categories</option>
                    @foreach ($categories as $categoryOption)
                        <option value="{{ $categoryOption }}" @selected($category === $categoryOption)>{{ str($categoryOption)->headline() }}</option>
                    @endforeach
                </select>
                <select name="assigned_to_user_id" class="form-input">
                    <option value="">All assignees</option>
                    @foreach ($assigneeOptions as $assigneeOption)
                        <option value="{{ $assigneeOption->id }}" @selected($assignedToUserId === $assigneeOption->id)>{{ $assigneeOption->name }}</option>
                    @endforeach
                </select>
                @if ($isSuperAdmin)
                    <select name="company_id" class="form-input">
                        <option value="">All companies</option>
                        @foreach ($companyOptions as $companyOption)
                            <option value="{{ $companyOption->id }}" @selected($companyId === $companyOption->id)>{{ $companyOption->name }}</option>
                        @endforeach
                    </select>
                @endif
                <div class="flex gap-2">
                    <button type="submit" class="btn-secondary">Filter</button>
                    <a href="{{ route('admin.tickets.index') }}" class="btn-secondary">Reset</a>
                    @can('create-ticket')
                        <a href="{{ route('admin.tickets.create') }}" class="btn-primary">Create ticket</a>
                    @endcan
                </div>
            </form>

            <div class="mt-5 overflow-x-auto rounded-2xl border" style="border-color: var(--panel-border);">
                <table class="min-w-[920px] w-full divide-y" style="border-color: var(--panel-border);">
                    <thead style="background: var(--panel-soft);">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.2em] text-muted">Ticket</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.2em] text-muted">Subject</th>
                            @if ($isSuperAdmin)
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.2em] text-muted">Company</th>
                            @endif
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.2em] text-muted">Priority</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.2em] text-muted">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.2em] text-muted">Requester</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.2em] text-muted">Assignee</th>
                            <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-[0.2em] text-muted">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" style="border-color: var(--panel-border);">
                        @forelse ($tickets as $ticket)
                            <tr>
                                <td class="px-4 py-3 text-sm font-semibold" style="color: var(--text-primary);">{{ $ticket->ticket_number }}</td>
                                <td class="px-4 py-3 text-sm" style="color: var(--text-primary);">
                                    <p class="font-semibold">{{ $ticket->subject }}</p>
                                    <p class="text-xs text-muted mt-1">{{ str($ticket->category)->headline() }} · {{ $ticket->created_at->diffForHumans() }}</p>
                                </td>
                                @if ($isSuperAdmin)
                                    <td class="px-4 py-3 text-sm" style="color: var(--text-primary);">{{ $ticket->company?->name ?? 'Global / Unknown' }}</td>
                                @endif
                                <td class="px-4 py-3 text-sm">{{ str($ticket->priority)->headline() }}</td>
                                <td class="px-4 py-3 text-sm">{{ str($ticket->status)->replace('_', ' ')->headline() }}</td>
                                <td class="px-4 py-3 text-sm">{{ $ticket->creator?->name ?? 'Unknown' }}</td>
                                <td class="px-4 py-3 text-sm">{{ $ticket->assignee?->name ?? 'Unassigned' }}</td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <a href="{{ route('admin.tickets.show', $ticket) }}" class="btn-secondary">Open</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isSuperAdmin ? 8 : 7 }}" class="px-4 py-10 text-center text-sm text-muted">No tickets found for the current filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $tickets->links() }}</div>
        </div>
    </div>
</x-app-layout>
