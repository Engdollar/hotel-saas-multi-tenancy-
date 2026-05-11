<x-app-layout>
    <x-slot name="header">
        <div>
            <h1>Company Profile</h1>
            <p class="text-sm text-muted">Manage your company name and domain. Branding and theme stay in Settings.</p>
        </div>
    </x-slot>

    <section class="panel p-5">
        <form method="POST" action="{{ route('admin.company-profile.update') }}" class="grid gap-4 md:grid-cols-2">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="text-sm font-semibold" style="color: var(--text-primary);">Company name</label>
                <input id="name" type="text" name="name" value="{{ old('name', $company->name) }}" class="form-input" required>
            </div>

            <div>
                <label for="domain" class="text-sm font-semibold" style="color: var(--text-primary);">Domain</label>
                <input id="domain" type="text" name="domain" value="{{ old('domain', $company->domain) }}" class="form-input" placeholder="{{ $tenancyBaseDomain ? 'somlogic or full host' : 'tenant.example.com' }}">
                @if (app()->environment('local') && $tenancyBaseDomain)
                    <p class="mt-2 text-xs text-muted">For Herd subdomains, use just the subdomain or the full host like <span style="color: var(--text-primary);">somlogic.{{ $tenancyBaseDomain }}</span> and map it locally with <span style="color: var(--text-primary);">scripts/register-tenant-subdomain.ps1</span>.</p>
                @endif
            </div>

            <div class="md:col-span-2">
                <button type="submit" class="btn-primary">Save company profile</button>
            </div>
        </form>
    </section>
</x-app-layout>
