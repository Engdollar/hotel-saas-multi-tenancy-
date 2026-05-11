<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-black" style="color: var(--text-primary);">Company Control Center</h1>
            <p class="mt-1 text-sm text-muted">Operate tenant lifecycle, filter workload, and review company health from one page.</p>
        </div>
    </x-slot>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="panel p-4 sm:p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted">Total Companies</p>
            <p class="mt-2 text-3xl font-black" style="color: var(--text-primary);">{{ $stats['total'] }}</p>
            <p class="mt-2 text-xs text-muted">All registered tenants</p>
        </article>
        <article class="panel p-4 sm:p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-600">Pending Approval</p>
            <p class="mt-2 text-3xl font-black text-amber-600">{{ $stats['pending'] }}</p>
            <p class="mt-2 text-xs text-muted">Needs Super Admin decision</p>
        </article>
        <article class="panel p-4 sm:p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">Active</p>
            <p class="mt-2 text-3xl font-black text-emerald-600">{{ $stats['active'] }}</p>
            <p class="mt-2 text-xs text-muted">Can access tenant workspace</p>
        </article>
        <article class="panel p-4 sm:p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Inactive</p>
            <p class="mt-2 text-3xl font-black text-slate-500">{{ $stats['inactive'] }}</p>
            <p class="mt-2 text-xs text-muted">Suspended from access</p>
        </article>
    </section>

    <section class="panel mt-6 p-5 sm:p-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="type-title text-lg">Create Company</h2>
            <p class="text-xs text-muted">Use this for manual company provisioning by platform operators.</p>
        </div>

        @if (app()->environment('local') && $tenancyBaseDomain)
            <p class="mt-3 text-xs text-muted">Local Herd tip: if you save a tenant domain like <span style="color: var(--text-primary);">somlogic.{{ $tenancyBaseDomain }}</span>, Windows must resolve that host too. Use <span style="color: var(--text-primary);">scripts/register-tenant-subdomain.ps1 -Subdomain somlogic</span>.</p>
        @endif

        <form method="POST" action="{{ route('admin.companies.store') }}" class="mt-5 grid gap-3 md:grid-cols-5">
            @csrf
            <div class="md:col-span-2">
                <x-input-label for="name" value="Company Name" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" required />
            </div>
            <div>
                <x-input-label for="domain" value="Domain or Subdomain" />
                <x-text-input id="domain" name="domain" type="text" class="mt-1 block w-full" placeholder="{{ $tenancyBaseDomain ? 'somlogic' : 'tenant.example.com' }}" />
                @if ($tenancyBaseDomain)
                    <p class="mt-1 text-xs text-muted">A plain subdomain is saved as <span style="color: var(--text-primary);">subdomain.{{ $tenancyBaseDomain }}</span>.</p>
                @endif
            </div>
            <div>
                <x-input-label for="status" value="Initial Status" />
                <select id="status" name="status" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900" required>
                    <option value="pending">Pending</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="flex items-end">
                <x-primary-button class="w-full justify-center">Create Company</x-primary-button>
            </div>
        </form>
    </section>

    <section class="panel mt-6 p-5 sm:p-6">
        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="type-title text-lg">Company Directory</h2>
                    <p class="mt-1 text-sm text-muted">Search by company name/domain and execute lifecycle actions instantly.</p>
                </div>
                <form method="GET" action="{{ route('admin.companies.index') }}" class="grid gap-3 sm:grid-cols-2 lg:flex lg:items-end lg:gap-2">
                    <div>
                        <x-input-label for="company_query" value="Search" />
                        <x-text-input id="company_query" name="query" type="text" class="mt-1 block w-full lg:w-64" value="{{ $filters['query'] }}" placeholder="Name or domain" />
                    </div>
                    <div>
                        <x-input-label for="company_status_filter" value="Status" />
                        <select id="company_status_filter" name="status" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 lg:w-44">
                            <option value="all" @selected($filters['status'] === 'all')>All statuses</option>
                            <option value="pending" @selected($filters['status'] === 'pending')>Pending</option>
                            <option value="active" @selected($filters['status'] === 'active')>Active</option>
                            <option value="inactive" @selected($filters['status'] === 'inactive')>Inactive</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="btn-primary">Apply</button>
                        <a href="{{ route('admin.companies.index') }}" class="btn-secondary">Reset</a>
                    </div>
                </form>
            </div>

            <div class="mt-1 flex flex-wrap gap-2 text-xs">
                <a href="{{ route('admin.companies.index', ['status' => 'all', 'query' => $filters['query']]) }}" class="selection-chip {{ $filters['status'] === 'all' ? 'is-selected' : '' }}">All</a>
                <a href="{{ route('admin.companies.index', ['status' => 'pending', 'query' => $filters['query']]) }}" class="selection-chip {{ $filters['status'] === 'pending' ? 'is-selected' : '' }}">Pending</a>
                <a href="{{ route('admin.companies.index', ['status' => 'active', 'query' => $filters['query']]) }}" class="selection-chip {{ $filters['status'] === 'active' ? 'is-selected' : '' }}">Active</a>
                <a href="{{ route('admin.companies.index', ['status' => 'inactive', 'query' => $filters['query']]) }}" class="selection-chip {{ $filters['status'] === 'inactive' ? 'is-selected' : '' }}">Inactive</a>
            </div>

            <form method="POST" action="{{ route('admin.companies.bulk-lifecycle') }}" data-company-bulk-form="true" class="mt-2 flex flex-col gap-2 rounded-2xl border p-3 sm:flex-row sm:items-center" style="border-color: var(--panel-border);">
                @csrf
                <p class="text-xs font-semibold text-muted" data-company-selected-count>0 selected</p>
                <select name="action" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900" required>
                    <option value="">Bulk lifecycle action...</option>
                    <option value="approve">Approve selected</option>
                    <option value="activate">Activate selected</option>
                    <option value="suspend">Suspend selected</option>
                </select>
                <button type="submit" class="btn-primary">Apply Bulk Action</button>
                <p class="text-xs text-muted">Select rows below first, then apply.</p>
            </form>
        </div>

        <div class="mt-5 overflow-x-auto rounded-2xl border" style="border-color: var(--panel-border);">
            <table class="min-w-[1100px] text-left text-sm xl:min-w-full">
                <thead>
                    <tr class="border-b bg-slate-50" style="border-color: var(--panel-border);">
                        <th class="px-4 py-3">
                            <input type="checkbox" data-company-select-all aria-label="Select all companies on this page">
                        </th>
                        <th class="px-4 py-3">Company</th>
                        <th class="px-4 py-3">Domain</th>
                        <th class="px-4 py-3">Users</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Lifecycle Actions</th>
                        <th class="px-4 py-3">Manual Update</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($companies as $company)
                        <tr class="border-b align-top" style="border-color: var(--panel-border);">
                            <td class="px-4 py-4">
                                <input type="checkbox" value="{{ $company->id }}" data-company-row-select aria-label="Select {{ $company->name }}">
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-semibold" style="color: var(--text-primary);">{{ $company->name }}</p>
                                <p class="mt-1 text-xs text-muted">Created {{ $company->created_at?->format('M d, Y') ?? 'n/a' }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <p class="text-sm" style="color: var(--text-primary);">{{ $company->domain ?: 'n/a' }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <span class="selection-chip is-selected">{{ $company->users_count }} users</span>
                            </td>
                            <td class="px-4 py-4">
                                <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $company->status === 'active' ? 'bg-emerald-100 text-emerald-700' : ($company->status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-slate-200 text-slate-700') }}">{{ ucfirst($company->status) }}</span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap gap-2">
                                    @if ($company->status === 'pending')
                                        <form method="POST" action="{{ route('admin.companies.approve', $company) }}">
                                            @csrf
                                            <button type="submit" class="rounded-full bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-emerald-500">Approve</button>
                                        </form>
                                    @endif

                                    @if ($company->status !== 'inactive')
                                        <form method="POST" action="{{ route('admin.companies.suspend', $company) }}">
                                            @csrf
                                            <button type="submit" class="rounded-full bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-rose-500">Suspend</button>
                                        </form>
                                    @endif

                                    @if ($company->status !== 'active')
                                        <form method="POST" action="{{ route('admin.companies.activate', $company) }}">
                                            @csrf
                                            <button type="submit" class="rounded-full bg-cyan-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-cyan-500">Activate</button>
                                        </form>
                                    @endif

                                    @if ($company->status !== 'pending')
                                        <form method="POST" action="{{ route('admin.companies.mark-pending', $company) }}">
                                            @csrf
                                            <button type="submit" class="rounded-full bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-amber-500">Set Pending</button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('admin.companies.switch') }}">
                                        @csrf
                                        <input type="hidden" name="company_id" value="{{ $company->id }}">
                                        <button type="submit" class="rounded-full bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-indigo-500">Impersonate Context</button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.companies.destroy', $company) }}" data-confirm-delete="true" data-confirm-title="Delete {{ addslashes($company->name) }}?" data-confirm-text="This will permanently remove the company and all tenant users, roles, and permissions." data-loading-message="Deleting company...">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-full bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-slate-700">Delete</button>
                                    </form>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <form method="POST" action="{{ route('admin.companies.update', $company) }}" class="grid gap-2 md:grid-cols-3">
                                    @csrf
                                    @method('PUT')
                                    <x-text-input name="name" type="text" value="{{ $company->name }}" class="block w-full" required />
                                    <x-text-input name="domain" type="text" value="{{ $company->domain }}" class="block w-full" placeholder="{{ $tenancyBaseDomain ? 'somlogic or full host' : 'tenant.example.com' }}" />
                                    <div class="flex items-center gap-2">
                                        <select name="status" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900" required>
                                            <option value="pending" @selected($company->status === 'pending')>Pending</option>
                                            <option value="active" @selected($company->status === 'active')>Active</option>
                                            <option value="inactive" @selected($company->status === 'inactive')>Inactive</option>
                                        </select>
                                        <x-primary-button>Save</x-primary-button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-muted">No companies matched your filter criteria.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $companies->links() }}
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const bulkForm = document.querySelector('[data-company-bulk-form="true"]');
            const selectAll = document.querySelector('[data-company-select-all]');
            const rowChecks = Array.from(document.querySelectorAll('[data-company-row-select]'));
            const selectedCount = document.querySelector('[data-company-selected-count]');

            if (!bulkForm || !rowChecks.length || !selectedCount) {
                return;
            }

            const syncSelectedCounter = () => {
                const selectedRows = rowChecks.filter((checkbox) => checkbox.checked);
                selectedCount.textContent = `${selectedRows.length} selected`;

                bulkForm.querySelectorAll('input[name="companies[]"]').forEach((input) => input.remove());

                selectedRows.forEach((checkbox) => {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'companies[]';
                    hidden.value = checkbox.value;
                    bulkForm.append(hidden);
                });

                if (selectAll) {
                    selectAll.checked = selectedRows.length === rowChecks.length;
                }
            };

            rowChecks.forEach((checkbox) => checkbox.addEventListener('change', syncSelectedCounter));

            selectAll?.addEventListener('change', () => {
                rowChecks.forEach((checkbox) => {
                    checkbox.checked = selectAll.checked;
                });

                syncSelectedCounter();
            });

            syncSelectedCounter();
        });
    </script>
</x-app-layout>
