@php($modal = $modal ?? false)

<div>
    <label for="name" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Permission name</label>
    <input id="name" name="name" type="text" class="form-input" value="{{ old('name', $permission->name ?? '') }}" placeholder="create-user" required>
    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Use the action-module format, for example create-user or update-role.</p>
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
    <p data-field-error="name" class="form-error-copy {{ $errors->has('name') ? '' : 'hidden' }}">{{ $errors->first('name') }}</p>
</div>

<div class="mt-8 flex items-center justify-end gap-3">
    @if ($modal)
        <button type="button" class="btn-secondary" data-modal-close>Cancel</button>
    @else
        <a href="{{ route('admin.permissions.index') }}" class="btn-secondary">Cancel</a>
    @endif
    <button type="submit" class="btn-primary">{{ isset($permission) ? 'Update permission' : 'Create permission' }}</button>
</div>