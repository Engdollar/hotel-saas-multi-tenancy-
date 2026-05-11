<?php

namespace App\Support;

class AssetPath
{
    public static function prefix(): string
    {
        $configured = trim((string) config('app.asset_path_prefix', ''), '/');

        if ($configured !== '') {
            return $configured;
        }

        return app()->environment('production') ? 'public' : '';
    }

    public static function prefixed(string $path): string
    {
        $prefix = self::prefix();
        $normalized = ltrim($path, '/');

        return $prefix === '' ? $normalized : $prefix.'/'.$normalized;
    }

    public static function buildBasePath(): string
    {
        return self::prefixed('build');
    }

    public static function storageBasePath(): string
    {
        return self::prefixed('storage');
    }

    public static function storageUrl(?string $path): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        return asset(self::storageBasePath().'/'.ltrim($path, '/'));
    }
}