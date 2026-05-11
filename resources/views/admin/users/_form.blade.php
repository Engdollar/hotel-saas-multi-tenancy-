@php($editing = isset($user))
@php($modal = $modal ?? false)
@php($lockedRoleNames = collect($lockedRoleNames ?? []))
@php($selectedRoles = old('roles', $editing ? $user->roles->where('is_locked', false)->pluck('name')->all() : []))
@php($roleOptions = $roles->map(fn ($role) => ['name' => $role->name, 'permissions_count' => $role->permissions_count ?? $role->permissions->count()])->values())

<div class="{{ $modal ? 'grid gap-6' : 'grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,0.95fr)]' }}" x-data='userForm({ existingImage: @json($editing && $user->profile_image_path ? asset("storage/{$user->profile_image_path}") : null) })'>
    <div class="min-w-0 space-y-6">
        <div class="user-preview-shell">
            <div class="user-preview-avatar">
                <template x-if="previewImage">
                    <img :src="previewImage" alt="Profile preview">
                </template>
                <template x-if="!previewImage">
                    <div class="flex h-full w-full items-center justify-center text-lg font-black" style="color: var(--text-soft);">{{ strtoupper(substr(old('name', $user->name ?? 'U'), 0, 1)) }}</div>
                </template>
            </div>
            <div class="min-w-0 flex-1">
                <p class="section-kicker">Live preview</p>
                <h3 class="mt-2 text-lg font-black" style="color: var(--text-primary);">Profile image and credential check</h3>
                <p class="mt-2 text-sm text-muted">Preview the avatar before saving, monitor password strength, and confirm matching credentials in one place.</p>
            </div>
        </div>

        <div>
            <label for="name" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Name</label>
            <input id="name" name="name" type="text" class="form-input" value="{{ old('name', $user->name ?? '') }}" required>
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
            <p data-field-error="name" class="form-error-copy {{ $errors->has('name') ? '' : 'hidden' }}">{{ $errors->first('name') }}</p>
        </div>

        <div>
            <label for="email" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Email</label>
            <input id="email" name="email" type="email" class="form-input" value="{{ old('email', $user->email ?? '') }}" required>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
            <p data-field-error="email" class="form-error-copy {{ $errors->has('email') ? '' : 'hidden' }}">{{ $errors->first('email') }}</p>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <label for="password" class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $editing ? 'New password' : 'Password' }}</label>
                <input id="password" name="password" type="password" class="form-input" {{ $editing ? '' : 'required' }} @input="updatePassword($event.target.value)">
                <div class="mt-3 flex items-center justify-between gap-3">
                    <div class="password-meter-track flex-1">
                        <div class="password-meter-fill" :style="`width: ${strengthWidth}`"></div>
                    </div>
                    <span class="password-status-pill" :class="`is-${passwordStrength.tone}`" x-text="passwordStrength.label"></span>
                </div>
                <p class="mt-2 text-sm text-muted" x-text="passwordStrength.hint"></p>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                <p data-field-error="password" class="form-error-copy {{ $errors->has('password') ? '' : 'hidden' }}">{{ $errors->first('password') }}</p>
            </div>

            <div>
                <label for="password_confirmation" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="form-input" {{ $editing ? '' : 'required' }} @input="updatePasswordConfirmation($event.target.value)">
                <div class="mt-3 flex items-center justify-between gap-3">
                    <p class="text-sm font-semibold" :class="passwordsMatch === false ? 'text-rose-500' : 'text-emerald-500'" x-text="matchLabel"></p>
                    <span class="selection-chip" :class="passwordsMatch === true ? 'is-selected' : ''" x-text="passwordsMatch === null ? 'Pending' : (passwordsMatch ? 'Match' : 'Check')"></span>
                </div>
            </div>
        </div>

        <div>
            <label for="profile_image" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Profile image</label>
            <input id="profile_image" name="profile_image" type="file" class="form-input" accept="image/*" @change="previewSelectedImage($event)">
            <x-input-error :messages="$errors->get('profile_image')" class="mt-2" />
            <p data-field-error="profile_image" class="form-error-copy {{ $errors->has('profile_image') ? '' : 'hidden' }}">{{ $errors->first('profile_image') }}</p>
        </div>
    </div>

    <div class="panel min-w-0 p-5 sm:p-6" x-data='rolePicker(@json($selectedRoles), @json($roleOptions), @json($lockedRoleNames->values()->all()))'>
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="section-kicker">Role assignment</p>
                <h3 class="mt-2 text-lg font-black" style="color: var(--text-primary);">Access control</h3>
                <p class="mt-2 text-sm text-muted">Search and assign editable company roles. Tenant system roles stay protected and cannot be changed from this form.</p>
            </div>
            <span class="selection-chip" x-text="`${totalSelectedCount} selected`"></span>
        </div>

        <div class="mt-5 flex flex-col gap-3 sm:flex-row">
            <input type="search" x-model="query" class="form-input mt-0 sm:flex-1" placeholder="Search 40+ roles quickly...">
            <button type="button" class="btn-secondary" @click="showSelectedOnly = !showSelectedOnly" x-text="showSelectedOnly ? 'Show all roles' : 'Show selected only'"></button>
            <button type="button" class="btn-secondary" @click="clearRoles" x-show="selected.length">Clear</button>
        </div>

        @if ($lockedRoleNames->isNotEmpty())
            <div class="protected-role-strip mt-5">
                <div>
                    <p class="section-kicker">Protected roles</p>
                    <h4 class="mt-2 text-base font-black" style="color: var(--text-primary);">Locked tenancy access</h4>
                    <p class="mt-2 text-sm text-muted">These company bootstrap roles are fixed and cannot be reassigned from the user editor.</p>
                </div>

                <div class="role-picker-chip-grid mt-4">
                    @foreach ($lockedRoleNames as $lockedRoleName)
                        <span class="role-picker-chip is-locked">
                            <span>{{ $lockedRoleName }}</span>
                            <span class="selection-chip is-selected">Locked</span>
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-5 {{ $modal ? 'grid gap-5' : 'role-picker-layout' }}">
            <div class="role-picker-list min-w-0">
                <template x-for="role in filteredRoles" :key="role.name">
                    <button type="button" class="role-picker-option" :class="isSelected(role.name) ? 'is-selected' : ''" @click="toggleRole(role.name)">
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold" style="color: var(--text-primary);" x-text="role.name"></span>
                            <span class="mt-1 block text-sm text-muted" x-text="`${role.permissions_count} permission(s)`"></span>
                        </span>
                        <span class="selection-chip" :class="isSelected(role.name) ? 'is-selected' : ''" x-text="isSelected(role.name) ? 'Assigned' : 'Assign'"></span>
                    </button>
                </template>

                <div x-show="!filteredRoles.length" class="surface-soft p-4 text-sm text-muted">
                    No roles match this filter.
                </div>
            </div>

            <div class="role-picker-selection min-w-0">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="section-kicker">Selected roles</p>
                        <h4 class="mt-2 text-base font-black" style="color: var(--text-primary);">Assignment summary</h4>
                    </div>
                    <span class="selection-chip" x-text="`${totalSelectedCount} total`"></span>
                </div>

                <div class="mt-4 space-y-3" x-show="lockedRoles.length">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.26em]" style="color: var(--text-soft);">Protected</p>
                        <div class="role-picker-chip-grid mt-3">
                            <template x-for="roleName in lockedRoles" :key="`locked-${roleName}`">
                                <span class="role-picker-chip is-locked">
                                    <span class="truncate" x-text="roleName"></span>
                                    <span class="selection-chip is-selected">Locked</span>
                                </span>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="role-picker-chip-grid mt-4" x-show="selectedRoles.length">
                    <template x-for="role in selectedRoles" :key="role.name">
                        <button type="button" class="role-picker-chip" @click="toggleRole(role.name)">
                            <span class="truncate" x-text="role.name"></span>
                            <x-icon name="x" class="h-4 w-4" />
                        </button>
                    </template>
                </div>

                <p x-show="!selectedRoles.length && !lockedRoles.length" class="mt-4 text-sm text-muted">No roles selected yet.</p>

                <div class="mt-5 space-y-3">
                    <template x-for="roleName in selected" :key="roleName">
                        <input type="hidden" name="roles[]" :value="roleName">
                    </template>
                </div>
            </div>
        </div>

        <x-input-error :messages="$errors->get('roles')" class="mt-2" />
        <p data-field-error="roles" class="form-error-copy {{ $errors->has('roles') ? '' : 'hidden' }}">{{ $errors->first('roles') }}</p>
    </div>
</div>

<div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
    @if ($modal)
        <button type="button" class="btn-secondary w-full sm:w-auto" data-modal-close>Cancel</button>
    @else
        <a href="{{ route('admin.users.index') }}" class="btn-secondary w-full sm:w-auto">Cancel</a>
    @endif
    <button type="submit" class="btn-primary w-full sm:w-auto">{{ $editing ? 'Update user' : 'Create user' }}</button>
</div>