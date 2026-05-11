<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-black" style="color: var(--text-primary);">Users</h1>
            <p class="mt-1 text-sm text-muted">Manage accounts, profile images, and role assignments.</p>
        </div>
    </x-slot>

    <div class="panel p-5 sm:p-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.3em] text-cyan-500">Directory</p>
                <h2 class="mt-2 text-xl font-black text-slate-900 dark:text-white">All users</h2>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row">
                <input id="users-search" type="search" placeholder="Search users..." class="form-input mt-0 sm:w-72">
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="btn-secondary" data-export-url="{{ route('admin.users.export', 'csv') }}" data-format="csv">CSV</button>
                    <button type="button" class="btn-secondary" data-export-url="{{ route('admin.users.export', 'xlsx') }}" data-format="xlsx" data-loading data-loading-message="Exporting users to Excel...">Excel</button>
                    <button type="button" class="btn-secondary" data-export-url="{{ route('admin.users.export', 'pdf') }}" data-format="pdf" data-loading data-loading-message="Exporting users to PDF...">PDF</button>
                </div>
                @can('create-user')
                    <a href="{{ route('admin.users.create') }}" data-modal-url="{{ route('admin.users.create') }}" class="btn-primary icon-label-button"><x-icon name="plus" class="h-4 w-4" />Create user</a>
                @endcan
            </div>
        </div>

        <div class="mt-6 overflow-x-auto rounded-3xl border border-slate-200 dark:border-slate-800">
            <table id="users-table" class="min-w-[720px] divide-y divide-slate-200 dark:divide-slate-800 xl:min-w-full">
                <thead class="bg-slate-50 dark:bg-slate-950/60">
                    <tr>
                        <th data-column data-index="0" class="cursor-pointer px-4 py-4 text-left text-xs font-bold uppercase tracking-[0.3em] text-slate-500">User</th>
                        <th data-column data-index="1" class="cursor-pointer px-4 py-4 text-left text-xs font-bold uppercase tracking-[0.3em] text-slate-500">Roles</th>
                        <th data-column data-index="2" class="cursor-pointer px-4 py-4 text-left text-xs font-bold uppercase tracking-[0.3em] text-slate-500">Created</th>
                        <th class="px-4 py-4 text-right text-xs font-bold uppercase tracking-[0.3em] text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-900"></tbody>
            </table>
        </div>

        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p id="users-info" class="text-sm text-slate-500 dark:text-slate-400"></p>
            <div id="users-pagination" class="flex items-center gap-3"></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.initServerTable({
                selector: '#users-table',
                endpoint: '{{ route('admin.users.data') }}',
                searchSelector: '#users-search',
                infoSelector: '#users-info',
                paginationSelector: '#users-pagination',
                exportSelector: '[data-export-url]',
                defaultOrder: { column: 2, dir: 'desc' },
                columns: [
                    { data: 'name', name: 'name' },
                    { data: 'roles_label', name: 'roles_label', orderable: false },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'actions', name: 'actions', searchable: false, orderable: false },
                ],
            });
        });
    </script>
</x-app-layout>