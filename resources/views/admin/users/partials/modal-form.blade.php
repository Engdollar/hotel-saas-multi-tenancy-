@php($editing = isset($user))

<div class="crud-modal-content">
    <div class="crud-modal-header">
        <div>
            <p class="section-kicker">{{ $editing ? 'Edit user' : 'Create user' }}</p>
            <h2 class="mt-2 text-2xl font-black" style="color: var(--text-primary);">{{ $editing ? $user->name : 'New account' }}</h2>
            <p class="mt-2 text-sm text-muted">{{ $editing ? 'Update account details, credentials, and access assignment.' : 'Add a new account with image, password checks, and role assignment.' }}</p>
        </div>
        <button type="button" class="icon-button" data-modal-close aria-label="Close form">
            <x-icon name="x" class="h-4 w-4" />
        </button>
    </div>

    <div class="crud-modal-body">
        <form method="POST" action="{{ $editing ? route('admin.users.update', $user) : route('admin.users.store') }}" enctype="multipart/form-data" data-modal-form="true" class="space-y-6">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            @include('admin.users._form', ['modal' => true])
        </form>
    </div>
</div>