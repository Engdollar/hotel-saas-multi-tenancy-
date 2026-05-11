<section class="profile-section profile-feed-card space-y-6">
    <header class="profile-section-header">
        <div class="profile-section-heading">
            <div>
                <p class="section-kicker">Security</p>
                <h2 class="profile-section-title">Update password</h2>
            </div>

            <span class="profile-section-icon"><x-icon name="settings" class="h-4 w-4" /></span>
        </div>

        <p class="profile-section-copy text-sm text-muted">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <div class="profile-inline-tip">
        <p class="font-semibold" style="color: var(--text-primary);">Security tip</p>
        <p class="mt-1 text-sm text-muted">Use a unique password that is hard to guess and avoid reusing it on other services.</p>
    </div>

    <form method="post" action="{{ route('password.update') }}" class="space-y-6">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" :value="__('Current Password')" />
            <input id="update_password_current_password" name="current_password" type="password" class="form-input" autocomplete="current-password">
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="profile-field-block">
                <x-input-label for="update_password_password" :value="__('New Password')" />
                <input id="update_password_password" name="password" type="password" class="form-input" autocomplete="new-password">
                <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
            </div>

            <div class="profile-field-block">
                <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" />
                <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="form-input" autocomplete="new-password">
                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="btn-primary">Update password</button>

            <span class="profile-save-hint">Your next sign-in will use the new password immediately.</span>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm"
                    style="color: var(--accent-strong);"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
