@php
    $hasOnlyLockedBootstrapRole = $user->roles->count() === 1
        && $user->roles->first()?->is_locked
        && $user->roles->first()?->company_id !== null;
@endphp

<div class="flex justify-end gap-2">
    @can('show-user')
        <a href="{{ route('admin.users.show', $user) }}" class="icon-button" title="View user" aria-label="View user"><x-icon name="eye" class="h-4 w-4" /></a>
    @endcan
    @can('edit-user')
        @if (! $hasOnlyLockedBootstrapRole)
        <a href="{{ route('admin.users.edit', $user) }}" data-modal-url="{{ route('admin.users.edit', $user) }}" class="icon-button" title="Edit user" aria-label="Edit user"><x-icon name="pencil" class="h-4 w-4" /></a>
        @endif
    @endcan
    @can('delete-user')
        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" data-confirm-delete="true" data-confirm-title="Delete {{ addslashes($user->name) }}?" data-confirm-text="This user account, image, and role assignment will be removed." data-loading-message="Deleting user...">
            @csrf
            @method('DELETE')
            <button type="submit" class="icon-button" style="background: rgba(209, 93, 85, 0.14); color: #b43d36; border-color: rgba(180, 61, 54, 0.2);" title="Delete user" aria-label="Delete user"><x-icon name="trash" class="h-4 w-4" /></button>
        </form>
    @endcan
</div>