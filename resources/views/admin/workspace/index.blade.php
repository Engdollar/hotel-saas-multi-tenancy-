<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="type-page-title" style="color: var(--text-primary);">{{ $module['label'] }}</h1>
            <p class="type-body mt-1 text-muted">{{ $module['description'] }}</p>
        </div>
    </x-slot>

    @if (! empty($module['create_route']))
        <x-slot name="headerActions">
            <a href="{{ $module['create_route'] }}" class="btn-primary icon-label-button">
                <x-icon name="plus" class="h-4 w-4" />
                <span>Create</span>
            </a>
        </x-slot>
    @endif

    <div class="space-y-5">
        @if (! empty($summary))
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($summary as $metric)
                    <div class="panel p-5">
                        <p class="section-kicker">Snapshot</p>
                        <p class="type-section-title mt-2" style="color: var(--text-primary);">{{ $metric['value'] }}</p>
                        <p class="type-card-title mt-2" style="color: var(--text-primary);">{{ $metric['label'] }}</p>
                        <p class="type-body mt-1 text-muted">{{ $metric['description'] }}</p>
                    </div>
                @endforeach
            </div>
        @endif

        @if (! empty($tableControls['search']) || ! empty($tableControls['filters']))
            <div class="panel p-5">
                <form method="GET" action="{{ $module['route'] }}" class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_repeat(3,minmax(0,14rem))_auto]">
                    @if (! empty($tableControls['search']))
                        <div>
                            <label class="mb-2 block text-sm font-semibold" style="color: var(--text-primary);">Search</label>
                            <input type="text" name="{{ $tableControls['search']['name'] }}" value="{{ $tableControls['search']['value'] }}" placeholder="{{ $tableControls['search']['placeholder'] }}" class="w-full rounded-2xl border px-4 py-3 text-sm" style="border-color: var(--panel-border); background: var(--panel-bg); color: var(--text-primary);">
                        </div>
                    @endif

                    @foreach ($tableControls['filters'] ?? [] as $filter)
                        <div>
                            <label class="mb-2 block text-sm font-semibold" style="color: var(--text-primary);">{{ $filter['label'] }}</label>
                            <select name="{{ $filter['name'] }}" class="w-full rounded-2xl border px-4 py-3 text-sm" style="border-color: var(--panel-border); background: var(--panel-bg); color: var(--text-primary);">
                                <option value="">All {{ strtolower($filter['label']) }}</option>
                                @foreach ($filter['options'] as $option)
                                    <option value="{{ $option['value'] }}" @selected((string) $filter['value'] === (string) $option['value'])>{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach

                    <div class="flex items-end gap-3">
                        <button type="submit" class="btn-primary">Apply</button>
                        <a href="{{ $module['route'] }}" class="btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
        @endif

        <div class="panel overflow-hidden">
            <div class="border-b px-5 py-4" style="border-color: var(--panel-border);">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div>
                        <p class="section-kicker">Workspace module</p>
                        <h2 class="type-section-title mt-1" style="color: var(--text-primary);">{{ $module['label'] }} records</h2>
                    </div>

                    @if (! empty($tableControls['bulk_actions']))
                        <div class="text-sm text-muted">Select rows below to run a bulk action.</div>
                    @endif
                </div>
            </div>
            <form method="POST" action="{{ route('admin.workspace.modules.bulk-actions.store', ['module' => $module['key']]) }}">
                @csrf
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y" style="divide-color: var(--panel-border);">
                        <thead>
                            <tr>
                                @if (! empty($tableControls['bulk_actions']))
                                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.14em] text-muted">Select</th>
                                @endif
                                @foreach ($columns as $column)
                                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.14em] text-muted">{{ $column }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y" style="divide-color: var(--panel-border);">
                            @forelse ($rows as $row)
                                @php
                                    $cells = $row['cells'] ?? $row;
                                    $recordUrl = $row['url'] ?? null;
                                @endphp
                                <tr>
                                    @if (! empty($tableControls['bulk_actions']))
                                        <td class="px-5 py-3 text-sm" style="color: var(--text-primary);">
                                            <input type="checkbox" name="record_ids[]" value="{{ $row['id'] }}" class="rounded border" style="border-color: var(--panel-border);">
                                        </td>
                                    @endif
                                    @foreach ($cells as $index => $cell)
                                        <td class="px-5 py-3 text-sm" style="color: var(--text-primary);">
                                            @if ($index === 0 && $recordUrl)
                                                <a href="{{ $recordUrl }}" class="font-semibold hover:underline">{{ $cell }}</a>
                                            @else
                                                {{ $cell }}
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($columns) + (! empty($tableControls['bulk_actions']) ? 1 : 0) }}" class="px-5 py-8 text-sm text-muted">No records are available for this module yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (! empty($tableControls['bulk_actions']))
                    <div class="border-t px-5 py-4" style="border-color: var(--panel-border);">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex flex-1 items-center gap-3">
                                <select name="bulk_action" class="w-full max-w-sm rounded-2xl border px-4 py-3 text-sm" style="border-color: var(--panel-border); background: var(--panel-bg); color: var(--text-primary);">
                                    <option value="">Select bulk action</option>
                                    @foreach ($tableControls['bulk_actions'] as $action)
                                        <option value="{{ $action['value'] }}">{{ $action['label'] }}</option>
                                    @endforeach
                                </select>
                                <select name="bulk_user_id" class="w-full max-w-sm rounded-2xl border px-4 py-3 text-sm" style="border-color: var(--panel-border); background: var(--panel-bg); color: var(--text-primary); display: {{ ! empty($tableControls['bulk_user_options']) ? 'block' : 'none' }};">
                                    <option value="">Select assignee</option>
                                    @foreach ($tableControls['bulk_user_options'] ?? [] as $option)
                                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn-primary">Run bulk action</button>
                        </div>

                        @error('record_ids')
                            <p class="mt-2 text-sm text-rose-400">{{ $message }}</p>
                        @enderror
                        @error('bulk_action')
                            <p class="mt-2 text-sm text-rose-400">{{ $message }}</p>
                        @enderror
                        @error('bulk_user_id')
                            <p class="mt-2 text-sm text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
            </form>

            @if ($paginator)
                <div class="border-t px-5 py-4" style="border-color: var(--panel-border);">
                    {{ $paginator->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>