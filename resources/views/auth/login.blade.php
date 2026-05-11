<x-guest-layout illustration="login">
    <div>
        <p class="text-sm font-bold uppercase tracking-[0.3em]" style="color: var(--accent);">Workspace access</p>
        <h1 class="mt-3 text-3xl font-black sm:text-4xl" style="color: var(--text-primary);">Welcome back to {{ $appSettings->get('project_title', config('app.name', 'Laravel')) }}.</h1>
        <p class="mt-3 text-sm text-muted">Sign in to your company workspace. On custom domains and subdomains, branding follows that company automatically.</p>
    </div>

    <x-auth-session-status class="mt-6" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <label for="email" class="text-sm font-semibold" style="color: var(--text-primary);">Email</label>
            <input id="email" class="form-input" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <label for="password" class="text-sm font-semibold" style="color: var(--text-primary);">Password</label>
            <div class="relative">
                <input id="password" class="form-input pr-14" :type="isPasswordVisible('login-password') ? 'text' : 'password'" name="password" required autocomplete="current-password" @focus="focusPasswordField('login-password')" @blur="blurPasswordField('login-password')">
                <button type="button" class="icon-button absolute right-1.5 top-[calc(50%+0.25rem)] -translate-y-1/2" :class="isPasswordVisible('login-password') ? 'is-accent' : ''" @mousedown.prevent @click="togglePasswordVisibility('login-password')" :aria-label="isPasswordVisible('login-password') ? 'Hide password' : 'Show password'" :title="isPasswordVisible('login-password') ? 'Hide password' : 'Show password'">
                    <x-icon name="eye" class="h-4 w-4" x-show="!isPasswordVisible('login-password')" x-cloak />
                    <x-icon name="eye-off" class="h-4 w-4" x-show="isPasswordVisible('login-password')" x-cloak />
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex flex-col gap-3 text-sm sm:flex-row sm:items-center sm:justify-between">
            <label for="remember_me" class="inline-flex items-center gap-2" style="color: var(--text-muted);">
                <input id="remember_me" type="checkbox" class="rounded shadow-sm" name="remember" style="border-color: var(--field-border); color: var(--accent);">
                <span>Remember me</span>
            </label>

            @if (Route::has('password.request'))
                <a class="font-semibold transition hover:opacity-80" style="color: var(--text-muted);" href="{{ route('password.request') }}">Forgot password?</a>
            @endif
        </div>

        <button type="submit" class="btn-primary w-full">Log in</button>

        @if (Route::has('register'))
            <p class="text-center text-sm text-muted">
                Need a workspace?
                <a href="{{ route('register') }}" class="font-semibold" style="color: var(--accent);">Create your company</a>
            </p>
        @endif
    </form>
</x-guest-layout>
