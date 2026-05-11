<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class TenancyDomainService
{
    public function baseDomain(): string
    {
        try {
            if (Schema::hasTable('settings')) {
                $stored = Setting::withoutGlobalScopes()
                    ->whereNull('company_id')
                    ->where('key', 'tenancy_base_domain')
                    ->value('value');

                if (is_string($stored) && trim($stored) !== '') {
                    return Str::lower(trim($stored));
                }
            }
        } catch (Throwable) {
        }

        return Str::lower(trim((string) config('tenancy.base_domain', '')));
    }

    public function qualifyDomain(?string $value): ?string
    {
        $domain = Str::lower(trim((string) $value));

        if ($domain === '') {
            return null;
        }

        $domain = preg_replace('#^https?://#', '', $domain) ?? $domain;
        $domain = explode('/', $domain)[0] ?? $domain;
        $baseDomain = $this->baseDomain();

        if ($baseDomain !== '' && ! str_contains($domain, '.')) {
            return $domain.'.'.$baseDomain;
        }

        return $domain;
    }

    public function sessionDomain(): ?string
    {
        $baseDomain = $this->baseDomain();

        return $baseDomain !== '' ? '.'.$baseDomain : null;
    }

    public function isBaseDomainHost(string $host): bool
    {
        $baseDomain = $this->baseDomain();

        return $baseDomain !== '' && Str::lower(trim($host)) === $baseDomain;
    }
}