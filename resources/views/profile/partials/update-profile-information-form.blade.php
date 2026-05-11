<section class="profile-section profile-feed-card space-y-6">
    <header class="profile-section-header">
        <div class="profile-section-heading">
            <div>
                <p class="section-kicker">Identity</p>
                <h2 class="profile-section-title">Profile information</h2>
            </div>

            <span class="profile-section-icon"><x-icon name="user" class="h-4 w-4" /></span>
        </div>

        <p class="profile-section-copy text-sm text-muted">
            Update your name, email address, and profile image.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form
        method="post"
        action="{{ route('profile.update') }}"
        enctype="multipart/form-data"
        class="space-y-6"
        x-data="{
            previewUrl: null,
            previewName: '',
            updatePreview(event) {
                const [file] = event.target.files || [];

                if (!file) {
                    this.clearPreview();
                    return;
                }

                this.previewName = file.name;

                const reader = new FileReader();
                reader.onload = (loadEvent) => {
                    this.previewUrl = loadEvent.target?.result || null;
                };
                reader.readAsDataURL(file);
            },
            clearPreview() {
                this.previewUrl = null;
                this.previewName = '';
                if (this.$refs.profileImageInput) {
                    this.$refs.profileImageInput.value = '';
                }
            }
        }"
    >
        @csrf
        @method('patch')

        <div class="profile-upload-card">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <div class="profile-preview-stack shrink-0">
                    <div class="profile-avatar-shell shrink-0" x-show="!previewUrl">
                        <x-avatar :user="$user" size="h-20 w-20 rounded-[1.5rem]" textSize="text-2xl" />
                    </div>

                    <div class="profile-preview-frame" x-show="previewUrl" x-cloak>
                        <img :src="previewUrl" :alt="previewName || 'Selected profile image preview'" class="profile-preview-image">
                    </div>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold" style="color: var(--text-primary);">Refresh your profile image</p>
                    <p class="mt-1 text-sm text-muted">Use a crisp square image so your account feels recognizable in menus, reports, and activity records.</p>

                    <div class="profile-preview-note" x-show="previewUrl" x-cloak>
                        <p class="text-sm font-semibold" style="color: var(--text-primary);">Preview ready</p>
                        <p class="mt-1 text-sm text-muted" x-text="previewName"></p>
                    </div>
                </div>
                <div class="w-full sm:w-auto sm:min-w-[16rem]">
                    <label for="profile_image" class="text-sm font-semibold" style="color: var(--text-primary);">Profile image</label>
                    <input id="profile_image" name="profile_image" type="file" class="form-input profile-file-input" accept="image/*" x-ref="profileImageInput" @change="updatePreview($event)">

                    <div class="mt-3 flex flex-wrap items-center gap-2" x-show="previewUrl" x-cloak>
                        <button type="button" class="btn-secondary" @click="clearPreview()">Remove preview</button>
                        <span class="text-sm text-muted">Preview first, then save changes.</span>
                    </div>

                    <x-input-error class="mt-2" :messages="$errors->get('profile_image')" />
                </div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="profile-field-block">
                <x-input-label for="name" :value="__('Name')" />
                <input id="name" name="name" type="text" class="form-input" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>

            <div class="profile-field-block">
                <x-input-label for="email" :value="__('Email')" />
                <input id="email" name="email" type="email" class="form-input" value="{{ old('email', $user->email) }}" required autocomplete="username">
                <x-input-error class="mt-2" :messages="$errors->get('email')" />
            </div>
        </div>

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="notice-card">
                <p class="font-semibold" style="color: var(--text-primary);">Your email address is not verified yet.</p>
                <p class="mt-1 text-sm text-muted">Verify it to keep recovery and security flows working correctly.</p>

                <button form="send-verification" class="mt-3 text-sm font-semibold" style="color: var(--accent);">
                    {{ __('Re-send verification email') }}
                </button>

                @if (session('status') === 'verification-link-sent')
                    <p class="mt-3 text-sm font-medium" style="color: var(--accent-strong);">
                        {{ __('A fresh verification link has been sent to your email address.') }}
                    </p>
                @endif
            </div>
        @endif

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="btn-primary">Save changes</button>

            <span class="profile-save-hint">Changes update your profile across the admin workspace.</span>

            @if (session('status') === 'profile-updated')
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
