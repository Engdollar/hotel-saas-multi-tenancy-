<x-guest-layout illustration="register">
    <div>
        <p class="text-sm font-bold uppercase tracking-[0.3em]" style="color: var(--accent);">Company sign up</p>
        <h1 class="mt-4 text-4xl font-black" style="color: var(--text-primary);">Launch your company workspace.</h1>
        <p class="mt-3 text-sm text-muted">Create a company, become its first admin, and manage branding, theme, and tenant settings from your own dashboard.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <label for="company_name" class="text-sm font-semibold" style="color: var(--text-primary);">Company name</label>
            <input id="company_name" class="form-input" type="text" name="company_name" value="{{ old('company_name') }}" required autocomplete="organization">
            <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="subdomain" class="text-sm font-semibold" style="color: var(--text-primary);">Subdomain</label>
                <input id="subdomain" class="form-input" type="text" name="subdomain" value="{{ old('subdomain') }}" placeholder="acme">
                <p class="mt-2 text-xs text-muted">Choose this for auto-generated tenant URLs from your configured base domain.</p>
                @php($tenancyBaseDomain = app(\App\Services\TenancyDomainService::class)->baseDomain())
                @if (app()->environment('local') && $tenancyBaseDomain)
                    <p class="mt-2 text-xs text-muted">Local Herd: a value like <span style="color: var(--text-primary);">somlogic</span> becomes <span style="color: var(--text-primary);">somlogic.{{ $tenancyBaseDomain }}</span>. On Windows, map that host locally with <span style="color: var(--text-primary);">scripts/register-tenant-subdomain.ps1</span>.</p>
                @endif
                <x-input-error :messages="$errors->get('subdomain')" class="mt-2" />
            </div>

            <div>
                <label for="custom_domain" class="text-sm font-semibold" style="color: var(--text-primary);">Custom domain</label>
                <input id="custom_domain" class="form-input" type="text" name="custom_domain" value="{{ old('custom_domain') }}" placeholder="portal.acme.com">
                <p class="mt-2 text-xs text-muted">Use this only if you already own a custom domain. Leave both fields empty to stay on the platform domain.</p>
                <x-input-error :messages="$errors->get('custom_domain')" class="mt-2" />
            </div>
        </div>
        <p class="-mt-2 text-xs text-muted">Use either subdomain or custom domain. If both are provided, custom domain takes priority.</p>

        <div>
            <label for="name" class="text-sm font-semibold" style="color: var(--text-primary);">Admin name</label>
            <input id="name" class="form-input" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <label for="email" class="text-sm font-semibold" style="color: var(--text-primary);">Admin email</label>
            <input id="email" class="form-input" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <label for="password" class="text-sm font-semibold" style="color: var(--text-primary);">Password</label>
            <div class="relative">
                <input id="password" class="form-input pr-14" :type="isPasswordVisible('register-password') ? 'text' : 'password'" name="password" required autocomplete="new-password" @focus="focusPasswordField('register-password')" @blur="blurPasswordField('register-password')" @input="updatePasswordStrength($event.target.value, 'register-password')">
                <button type="button" class="icon-button absolute right-1.5 top-[calc(50%+0.25rem)] -translate-y-1/2" :class="isPasswordVisible('register-password') ? 'is-accent' : ''" @mousedown.prevent @click="togglePasswordVisibility('register-password')" :aria-label="isPasswordVisible('register-password') ? 'Hide password' : 'Show password'" :title="isPasswordVisible('register-password') ? 'Hide password' : 'Show password'">
                    <x-icon name="eye" class="h-4 w-4" x-show="!isPasswordVisible('register-password')" x-cloak />
                    <x-icon name="eye-off" class="h-4 w-4" x-show="isPasswordVisible('register-password')" x-cloak />
                </button>
            </div>
            <div class="mt-3 space-y-2" x-show="activePasswordField === 'register-password' || passwordStrength.score > 0" x-cloak>
                <div class="flex items-center justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em]" style="color: var(--text-muted);">Password strength</p>
                    <p class="text-xs font-black uppercase tracking-[0.18em]" :style="passwordStrength.tone === 'strong' ? 'color: var(--accent-strong);' : passwordStrength.tone === 'good' ? 'color: var(--accent);' : 'color: var(--text-muted);'" x-text="passwordStrength.label"></p>
                </div>
                <div class="grid grid-cols-4 gap-2">
                    <template x-for="segment in [1, 2, 3, 4]" :key="segment">
                        <span class="h-2 rounded-full transition" :style="segment <= passwordStrength.score ? (passwordStrength.tone === 'strong' ? 'background: linear-gradient(135deg, var(--accent) 0%, var(--accent-strong) 100%); box-shadow: 0 8px 18px -10px var(--shadow-color);' : passwordStrength.tone === 'good' ? 'background: color-mix(in srgb, var(--accent) 76%, white);' : 'background: color-mix(in srgb, #d67f5f 70%, var(--accent));') : 'background: color-mix(in srgb, var(--panel-border) 100%, transparent);'"></span>
                    </template>
                </div>
                <p class="text-xs leading-5 text-muted" x-text="passwordStrength.hint"></p>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <label for="password_confirmation" class="text-sm font-semibold" style="color: var(--text-primary);">Confirm password</label>
            <div class="relative">
                <input id="password_confirmation" class="form-input pr-14" :type="isPasswordVisible('register-password-confirmation') ? 'text' : 'password'" name="password_confirmation" required autocomplete="new-password" @focus="focusPasswordField('register-password-confirmation')" @blur="blurPasswordField('register-password-confirmation')">
                <button type="button" class="icon-button absolute right-1.5 top-[calc(50%+0.25rem)] -translate-y-1/2" :class="isPasswordVisible('register-password-confirmation') ? 'is-accent' : ''" @mousedown.prevent @click="togglePasswordVisibility('register-password-confirmation')" :aria-label="isPasswordVisible('register-password-confirmation') ? 'Hide password' : 'Show password'" :title="isPasswordVisible('register-password-confirmation') ? 'Hide password' : 'Show password'">
                    <x-icon name="eye" class="h-4 w-4" x-show="!isPasswordVisible('register-password-confirmation')" x-cloak />
                    <x-icon name="eye-off" class="h-4 w-4" x-show="isPasswordVisible('register-password-confirmation')" x-cloak />
                </button>
            </div>
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <button type="submit" class="btn-primary w-full">Create company workspace</button>

        <p class="text-center text-sm text-muted">
            Already registered?
            <a href="{{ route('login') }}" class="font-semibold" style="color: var(--accent);">Sign in</a>
        </p>
    </form>
</x-guest-layout>
