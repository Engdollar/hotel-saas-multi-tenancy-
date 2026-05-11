@php($editing = isset($permission))

<div class="crud-modal-content">
    <div class="crud-modal-header">
        <div>
            <p class="section-kicker">{{ $editing ? 'Edit permission' : 'Create permission' }}</p>
            <h2 class="mt-2 text-2xl font-black" style="color: var(--text-primary);">{{ $editing ? $permission->name : 'New permission' }}</h2>
            <p class="mt-2 text-sm text-muted">{{ $editing ? 'Rename the permission without leaving the inventory list.' : 'Add a permission to the catalog in a quick modal flow.' }}</p>
        </div>
        <button type="button" class="icon-button" data-modal-close aria-label="Close form">
            <x-icon name="x" class="h-4 w-4" />
        </button>
    </div>

    <div class="crud-modal-body">
        <form method="POST" action="{{ $editing ? route('admin.permissions.update', $permission) : route('admin.permissions.store') }}" data-modal-form="true" class="space-y-6">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            @include('admin.permissions._form', ['modal' => true])
        </form>
    </div>
</div>