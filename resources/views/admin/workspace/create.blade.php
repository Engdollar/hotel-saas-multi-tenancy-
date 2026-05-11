<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="type-page-title" style="color: var(--text-primary);">{{ $form_title ?? ('Create '.$module['label']) }}</h1>
            <p class="type-body mt-1 text-muted">{{ $module['description'] }}</p>
        </div>
    </x-slot>

    <div class="panel p-5 sm:p-6">
        <form method="POST" action="{{ $form_action ?? route('admin.workspace.modules.store', ['module' => $module['key']]) }}" class="grid gap-4 md:grid-cols-2">
            @csrf
            @if (($form_method ?? 'POST') !== 'POST')
                @method($form_method)
            @endif

            @foreach ($fields as $field)
                <div class="{{ $field['type'] === 'textarea' ? 'md:col-span-2' : '' }}">
                    @if ($field['type'] === 'checkbox')
                        <label class="flex items-center gap-3 rounded-2xl border px-4 py-3" style="border-color: var(--panel-border); color: var(--text-primary);">
                            <input type="checkbox" name="{{ $field['name'] }}" value="1" @checked((bool) old($field['name'], $field['value'] ?? false))>
                            <span>{{ $field['label'] }}</span>
                        </label>
                    @else
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
                    @endif

                    @error($field['name'])
                        <p class="mt-2 text-sm text-rose-400">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            <div class="md:col-span-2 flex items-center justify-end gap-3">
                <a href="{{ $cancel_url ?? $module['route'] }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">{{ $submit_label }}</button>
            </div>
        </form>
    </div>
</x-app-layout>