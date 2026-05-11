@php($editing = isset($role))
@php($modal = $modal ?? false)
@php($selectedPermissions = old('permissions', $editing ? $role->permissions->pluck('name')->all() : []))
@php($permissionMap = $permissionGroups->map(fn ($permissions) => $permissions->pluck('name')->values())->toArray())

<div>
    <label for="name" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Role name</label>
    <input id="name" name="name" type="text" class="form-input" value="{{ old('name', $role->name ?? '') }}" required>
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
    <p data-field-error="name" class="form-error-copy {{ $errors->has('name') ? '' : 'hidden' }}">{{ $errors->first('name') }}</p>
</div>

<div class="mt-8" x-data='permissionMatrix(@json(array_values($selectedPermissions)), @json($permissionMap))'>
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="section-kicker">Permission matrix</p>
            <h3 class="mt-2 text-xl font-black" style="color: var(--text-primary);">Assign capabilities</h3>
            <p class="mt-2 text-sm text-muted">Use global and per-module select-all controls so large permission sets stay manageable.</p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row">
            <input type="search" x-model="query" class="form-input mt-0 sm:w-72" placeholder="Filter permissions...">
            <button type="button" @click="toggleAll" class="btn-secondary icon-label-button"><x-icon name="check-square" class="h-4 w-4" />Select all visible groups</button>
        </div>
    </div>

    <div class="mt-6 space-y-6">
        @foreach ($permissionGroups as $module => $permissions)
            <div x-show="moduleMatches(@json($permissions->pluck('name')->values()))" class="surface-soft p-5">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h4 class="text-lg font-bold" style="color: var(--text-primary);">{{ $module }}</h4>
                        <span class="text-sm text-muted">{{ $permissions->count() }} permissions</span>
                    </div>
                    <button type="button" @click='toggleGroup(@json($permissions->pluck("name")->values()))' class="btn-secondary icon-label-button px-4 py-2">
                        <x-icon name="check-square" class="h-4 w-4" />
                        <span x-show='!allSelected(@json($permissions->pluck("name")->values()))'>Select all</span>
                        <span x-show='allSelected(@json($permissions->pluck("name")->values()))'>Clear all</span>
                    </button>
                </div>
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($permissions as $permission)
                        <label x-show='matches(@json($permission->name))' class="surface-soft flex items-center gap-3 px-4 py-3">
                            <input x-model="selected" type="checkbox" name="permissions[]" value="{{ $permission->name }}" class="rounded border-slate-300 text-cyan-500 focus:ring-cyan-500" @checked(in_array($permission->name, $selectedPermissions, true))>
                            <span class="text-sm font-medium" style="color: var(--text-primary);">{{ $permission->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>

<x-input-error :messages="$errors->get('permissions')" class="mt-2" />
<p data-field-error="permissions" class="form-error-copy {{ $errors->has('permissions') ? '' : 'hidden' }}">{{ $errors->first('permissions') }}</p>

<div class="mt-8 flex items-center justify-end gap-3">
    @if ($modal)
        <button type="button" class="btn-secondary" data-modal-close>Cancel</button>
    @else
        <a href="{{ route('admin.roles.index') }}" class="btn-secondary">Cancel</a>
    @endif
    <button type="submit" class="btn-primary">{{ $editing ? 'Update role' : 'Create role' }}</button>
</div>