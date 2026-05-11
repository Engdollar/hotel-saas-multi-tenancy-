<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="type-page-title" style="color: var(--text-primary);">{{ $title }}</h1>
            <p class="type-body mt-1 text-muted">{{ $subtitle }}</p>
        </div>
    </x-slot>

    <x-slot name="headerActions">
        <div class="flex items-center gap-3">
            @if (! empty($edit_url))
                <a href="{{ $edit_url }}" class="btn-primary icon-label-button">
                    <x-icon name="pencil" class="h-4 w-4" />
                    <span>Edit</span>
                </a>
            @endif
            <a href="{{ $module['route'] }}" class="btn-secondary icon-label-button">
                <x-icon name="arrow-left" class="h-4 w-4" />
                <span>Back to {{ $module['label'] }}</span>
            </a>
        </div>
    </x-slot>

    <div class="space-y-5">
        <div class="panel p-5 sm:p-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <p class="section-kicker">Tenant workspace record</p>
                    <h2 class="type-section-title mt-1" style="color: var(--text-primary);">{{ $title }}</h2>
                    @if ($status)
                        <p class="type-body mt-2 text-muted">Current status: {{ str($status)->headline() }}</p>
                    @endif
                </div>
                @if (! empty($metrics))
                    <div class="grid gap-3 sm:grid-cols-3 xl:min-w-[32rem]">
                        @foreach ($metrics as $metric)
                            <div class="surface-soft p-4">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-muted">{{ $metric['label'] }}</p>
                                <p class="type-card-title mt-2" style="color: var(--text-primary);">{{ $metric['value'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        @if (! empty($detailSections))
            <div class="grid gap-5 xl:grid-cols-2">
                @foreach ($detailSections as $section)
                    <div class="panel p-5">
                        <p class="section-kicker">Details</p>
                        <h2 class="type-section-title mt-1" style="color: var(--text-primary);">{{ $section['title'] }}</h2>
                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            @foreach ($section['items'] as $item)
                                <div class="surface-soft p-4">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-muted">{{ $item['label'] }}</p>
                                    <p class="mt-2 text-sm" style="color: var(--text-primary);">{{ $item['value'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if (! empty($tables))
            <div class="space-y-5">
                @foreach ($tables as $table)
                    <div class="panel overflow-hidden">
                        <div class="border-b px-5 py-4" style="border-color: var(--panel-border);">
                            <p class="section-kicker">Record data</p>
                            <h2 class="type-section-title mt-1" style="color: var(--text-primary);">{{ $table['title'] }}</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y" style="divide-color: var(--panel-border);">
                                <thead>
                                    <tr>
                                        @foreach ($table['columns'] as $column)
                                            <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.14em] text-muted">{{ $column }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="divide-y" style="divide-color: var(--panel-border);">
                                    @forelse ($table['rows'] as $row)
                                        <tr>
                                            @foreach ($row as $cell)
                                                <td class="px-5 py-3 text-sm" style="color: var(--text-primary);">{{ $cell }}</td>
                                            @endforeach
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ count($table['columns']) }}" class="px-5 py-8 text-sm text-muted">No records are available for this section yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if (! empty($actionForms))
            <div class="grid gap-5 xl:grid-cols-2">
                @foreach ($actionForms as $form)
                    <div class="panel p-5">
                        <p class="section-kicker">Workflow action</p>
                        <h2 class="type-section-title mt-1" style="color: var(--text-primary);">{{ $form['title'] }}</h2>
                        <p class="type-body mt-2 text-muted">{{ $form['description'] }}</p>

                        <form method="POST" action="{{ $form['route'] }}" class="mt-5 grid gap-4">
                            @csrf
                            @foreach ($form['fields'] as $field)
                                @if ($field['type'] === 'hidden')
                                    <input type="hidden" name="{{ $field['name'] }}" value="{{ old($field['name'], $field['value'] ?? '') }}">
                                @else
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold" style="color: var(--text-primary);">{{ $field['label'] }}@if (! empty($field['required'])) <span class="text-rose-400">*</span>@endif</label>
                                        @if ($field['type'] === 'select')
                                            <select name="{{ $field['name'] }}" class="w-full rounded-2xl border px-4 py-3 text-sm" style="border-color: var(--panel-border); background: var(--panel-bg); color: var(--text-primary);">
                                                <option value="">Select {{ strtolower($field['label']) }}</option>
                                                @foreach ($field['options'] as $option)
                                                    <option value="{{ $option['value'] }}" @selected((string) old($field['name'], $field['value'] ?? '') === (string) $option['value'])>{{ $option['label'] }}</option>
                                                @endforeach
                                            </select>
                                        @elseif ($field['type'] === 'textarea')
                                            <textarea name="{{ $field['name'] }}" rows="4" class="w-full rounded-2xl border px-4 py-3 text-sm" style="border-color: var(--panel-border); background: var(--panel-bg); color: var(--text-primary);">{{ old($field['name'], $field['value'] ?? '') }}</textarea>
                                        @else
                                            <input type="{{ $field['type'] }}" name="{{ $field['name'] }}" value="{{ old($field['name'], $field['value'] ?? '') }}" class="w-full rounded-2xl border px-4 py-3 text-sm" style="border-color: var(--panel-border); background: var(--panel-bg); color: var(--text-primary);">
                                        @endif
                                        @error($field['name'])
                                            <p class="mt-2 text-sm text-rose-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endif
                            @endforeach
                            <div class="flex justify-end">
                                <button type="submit" class="btn-primary">{{ $form['submit_label'] }}</button>
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>