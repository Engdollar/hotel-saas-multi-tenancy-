<div class="flex justify-end gap-2">
    @can('show-permission')
        <a href="{{ route('admin.permissions.show', $permission) }}" class="icon-button" title="View permission" aria-label="View permission"><x-icon name="eye" class="h-4 w-4" /></a>
    @endcan
    @can('edit-permission')
        <a href="{{ route('admin.permissions.edit', $permission) }}" data-modal-url="{{ route('admin.permissions.edit', $permission) }}" class="icon-button" title="Edit permission" aria-label="Edit permission"><x-icon name="pencil" class="h-4 w-4" /></a>
    @endcan
    @can('delete-permission')
        <form method="POST" action="{{ route('admin.permissions.destroy', $permission) }}" data-confirm-delete="true" data-confirm-title="Delete {{ addslashes($permission->name) }}?" data-confirm-text="This permission will be removed from the RBAC catalog." data-loading-message="Deleting permission...">
            @csrf
            @method('DELETE')
            <button type="submit" class="icon-button" style="background: rgba(209, 93, 85, 0.14); color: #b43d36; border-color: rgba(180, 61, 54, 0.2);" title="Delete permission" aria-label="Delete permission"><x-icon name="trash" class="h-4 w-4" /></button>
        </form>
    @endcan
</div>