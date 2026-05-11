<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class InstallerService
{
    public function allowsLocalInstallerAccess(): bool
    {
        return app()->environment(['local', 'testing']);
    }

    public function isInstalled(): bool
    {
        try {
            return Schema::hasTable('users')
                && User::withoutGlobalScopes()->exists();
        } catch (Throwable) {
            return false;
        }
    }

    public function install(array $payload): void
    {
        $baseDomain = strtolower(trim((string) ($payload['tenancy_base_domain'] ?? '')));

        $this->writeEnvironment([
            'APP_NAME' => $payload['project_title'],
            'APP_URL' => $payload['app_url'],
            'DB_HOST' => $payload['db_host'],
            'DB_PORT' => $payload['db_port'],
            'DB_DATABASE' => $payload['db_database'],
            'DB_USERNAME' => $payload['db_username'],
            'DB_PASSWORD' => $payload['db_password'],
            'TENANCY_RESOLVE_BY_DOMAIN' => $baseDomain !== '' ? 'true' : 'false',
            'TENANCY_BASE_DOMAIN' => $baseDomain,
            // Keep host-only cookies during install to avoid shared-host 419 issues.
            'SESSION_DOMAIN' => null,
            'RBAC_ADMIN_NAME' => $payload['admin_name'],
            'RBAC_ADMIN_EMAIL' => $payload['admin_email'],
            'RBAC_ADMIN_PASSWORD' => $payload['admin_password'],
            'DEFAULT_COMPANY_NAME' => $payload['default_company_name'],
            'DEFAULT_COMPANY_DOMAIN' => null,
        ]);

        // Session/encryption middleware can run before the next request cycle,
        // so ensure a key exists both on disk and in runtime config now.
        $appKey = $this->ensureApplicationKey();
        Config::set('app.key', $appKey);

        Artisan::call('optimize:clear');

        if (($payload['install_fresh'] ?? false) && $this->allowsLocalInstallerAccess()) {
            Artisan::call('migrate:fresh', ['--force' => true]);
        } else {
            Artisan::call('migrate', ['--force' => true]);
        }

        Artisan::call('system:setup', ['--refresh-passwords' => true]);

        if (Schema::hasTable('settings')) {
            Setting::withoutGlobalScopes()->updateOrCreate(
                ['company_id' => null, 'key' => 'project_title'],
                ['value' => $payload['project_title']],
            );

            Setting::withoutGlobalScopes()->updateOrCreate(
                ['company_id' => null, 'key' => 'tenancy_base_domain'],
                ['value' => $baseDomain],
            );
        }
    }

    public function defaults(): array
    {
        return [
            'project_title' => env('APP_NAME', config('app.name')),
            'app_url' => config('app.url'),
            'db_host' => env('DB_HOST', '127.0.0.1'),
            'db_port' => env('DB_PORT', '3306'),
            'db_database' => env('DB_DATABASE', ''),
            'db_username' => env('DB_USERNAME', ''),
            'db_password' => env('DB_PASSWORD', ''),
            'tenancy_base_domain' => env('TENANCY_BASE_DOMAIN', ''),
            'admin_name' => env('RBAC_ADMIN_NAME', 'Super Admin'),
            'admin_email' => env('RBAC_ADMIN_EMAIL', 'admin@example.com'),
            'default_company_name' => env('DEFAULT_COMPANY_NAME', 'Default Company'),
        ];
    }

    protected function writeEnvironment(array $values): void
    {
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            throw new RuntimeException('Unable to find the .env file for installation.');
        }

        $contents = File::get($envPath);

        foreach ($values as $key => $value) {
            $formatted = $this->formatEnvironmentValue($value);
            $pattern = "/^{$key}=.*$/m";

            if (preg_match($pattern, $contents) === 1) {
                $contents = preg_replace($pattern, "{$key}={$formatted}", $contents) ?? $contents;
                continue;
            }

            $contents = rtrim($contents).PHP_EOL."{$key}={$formatted}".PHP_EOL;
        }

        File::put($envPath, $contents);
    }

    protected function formatEnvironmentValue(mixed $value): string
    {
        $string = (string) ($value ?? '');

        if ($string === '') {
            return 'null';
        }

        if (preg_match('/\s/', $string) === 1) {
            return '"'.addcslashes($string, '"').'"';
        }

        return $string;
    }

    protected function ensureApplicationKey(): string
    {
        $existing = trim((string) env('APP_KEY', ''));

        if ($existing !== '' && strtolower($existing) !== 'null') {
            return $existing;
        }

        $generated = 'base64:'.base64_encode(random_bytes(32));

        $this->writeEnvironment([
            'APP_KEY' => $generated,
        ]);

        return $generated;
    }
}