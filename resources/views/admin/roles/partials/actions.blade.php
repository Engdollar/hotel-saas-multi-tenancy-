<div class="flex justify-end gap-2">
    @can('show-role')
        <a href="{{ route('admin.roles.show', $role) }}" class="icon-button" title="View role" aria-label="View role"><x-icon name="eye" class="h-4 w-4" /></a>
    @endcan
    @can('edit-role')
        @if (! $role->is_locked)
        <a href="{{ route('admin.roles.edit', $role) }}" data-modal-url="{{ route('admin.roles.edit', $role) }}" class="icon-button" title="Edit role" aria-label="Edit role"><x-icon name="pencil" class="h-4 w-4" /></a>
        @endif
    @endcan
    @can('delete-role')
        @if (! $role->is_locked)
        <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" data-confirm-delete="true" data-confirm-title="Delete {{ addslashes($role->name) }}?" data-confirm-text="This role will be removed from the RBAC catalog." data-loading-message="Deleting role...">
            @csrf
            @method('DELETE')
            <button type="submit" class="icon-button" style="background: rgba(209, 93, 85, 0.14); color: #b43d36; border-color: rgba(180, 61, 54, 0.2);" title="Delete role" aria-label="Delete role"><x-icon name="trash" class="h-4 w-4" /></button>
        </form>
        @endif
    @endcan
</div>