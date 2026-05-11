<section class="profile-section profile-feed-card space-y-6">
    <header class="profile-section-header">
        <div class="profile-section-heading">
            <div>
                <p class="section-kicker">Danger Zone</p>
                <h2 class="profile-section-title">Delete account</h2>
            </div>

            <span class="profile-section-icon is-danger"><x-icon name="trash" class="h-4 w-4" /></span>
        </div>

        <p class="profile-section-copy text-sm text-muted">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </header>

    <div class="profile-danger-card">
        <p class="font-semibold" style="color: var(--text-primary);">This action is permanent.</p>
        <p class="mt-1 text-sm text-muted">Deleting your account removes your access, profile data, and any personal settings tied to it.</p>

        <button
            type="button"
            class="btn-danger mt-4"
            x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        >{{ __('Delete Account') }}</button>
    </div>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-bold" style="color: var(--text-primary);">
                {{ __('Are you sure you want to delete your account?') }}
            </h2>

            <p class="mt-1 text-sm text-muted">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="{{ __('Password') }}" class="sr-only" />

                <input id="password" name="password" type="password" class="form-input w-3/4" placeholder="{{ __('Password') }}">

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    {{ __('Delete Account') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
