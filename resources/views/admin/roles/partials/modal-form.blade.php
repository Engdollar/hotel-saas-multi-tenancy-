@php($editing = isset($role))

<div class="crud-modal-content">
    <div class="crud-modal-header">
        <div>
            <p class="section-kicker">{{ $editing ? 'Edit role' : 'Create role' }}</p>
            <h2 class="mt-2 text-2xl font-black" style="color: var(--text-primary);">{{ $editing ? $role->name : 'New role' }}</h2>
            <p class="mt-2 text-sm text-muted">{{ $editing ? 'Refine naming and permission coverage without leaving the list.' : 'Define a role and assign its permissions from the same modal workflow.' }}</p>
        </div>
        <button type="button" class="icon-button" data-modal-close aria-label="Close form">
            <x-icon name="x" class="h-4 w-4" />
        </button>
    </div>

    <div class="crud-modal-body">
        <form method="POST" action="{{ $editing ? route('admin.roles.update', $role) : route('admin.roles.store') }}" data-modal-form="true" class="space-y-6">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            @include('admin.roles._form', ['modal' => true])
        </form>
    </div>
</div>