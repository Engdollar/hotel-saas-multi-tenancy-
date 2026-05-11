<?php

namespace App\Services;

class InstallerRequirementsService
{
    public function summary(): array
    {
        $requirements = [
            $this->phpVersionRequirement(),
            ...$this->extensionRequirements(),
            ...$this->writablePathRequirements(),
        ];

        return [
            'requirements' => $requirements,
            'passes' => collect($requirements)->every(fn (array $requirement) => $requirement['passes'] === true),
        ];
    }

    protected function phpVersionRequirement(): array
    {
        $requiredVersion = '8.2.0';
        $currentVersion = PHP_VERSION;

        return [
            'category' => 'PHP Runtime',
            'label' => 'PHP version',
            'expected' => '>='.$requiredVersion,
            'current' => $currentVersion,
            'passes' => version_compare($currentVersion, $requiredVersion, '>='),
            'help' => 'Laravel 12 requires PHP 8.2 or newer.',
        ];
    }

    protected function extensionRequirements(): array
    {
        return collect([
            ['extension' => 'bcmath', 'help' => 'Required for numeric helpers and framework features.'],
            ['extension' => 'ctype', 'help' => 'Required by the framework string and validation layer.'],
            ['extension' => 'curl', 'help' => 'Required for outbound HTTP integrations and some package features.'],
            ['extension' => 'dom', 'help' => 'Required by document export and HTML parsing libraries.'],
            ['extension' => 'fileinfo', 'help' => 'Required for uploaded file mime detection.'],
            ['extension' => 'gd', 'help' => 'Required for image processing and media previews.'],
            ['extension' => 'json', 'help' => 'Required by Laravel configuration and request handling.'],
            ['extension' => 'mbstring', 'help' => 'Required for multibyte string support.'],
            ['extension' => 'openssl', 'help' => 'Required for encryption, sessions, and secure HTTP.'],
            ['extension' => 'pdo', 'help' => 'Required for database access.'],
            ['extension' => 'pdo_mysql', 'help' => 'Required because this installer is configured for MySQL.'],
            ['extension' => 'tokenizer', 'help' => 'Required by the framework internals.'],
            ['extension' => 'xml', 'help' => 'Required by framework and export packages.'],
            ['extension' => 'zip', 'help' => 'Required for spreadsheet export support.'],
        ])->map(function (array $requirement) {
            return [
                'category' => 'PHP Extension',
                'label' => $requirement['extension'],
                'expected' => 'Installed',
                'current' => extension_loaded($requirement['extension']) ? 'Installed' : 'Missing',
                'passes' => extension_loaded($requirement['extension']),
                'help' => $requirement['help'],
            ];
        })->all();
    }

    protected function writablePathRequirements(): array
    {
        return collect([
            ['label' => '.env file', 'path' => base_path('.env')],
            ['label' => 'storage directory', 'path' => storage_path()],
            ['label' => 'bootstrap/cache directory', 'path' => base_path('bootstrap/cache')],
        ])->map(function (array $requirement) {
            $exists = file_exists($requirement['path']);
            $writable = $exists && is_writable($requirement['path']);

            return [
                'category' => 'Permissions',
                'label' => $requirement['label'],
                'expected' => 'Writable',
                'current' => ! $exists ? 'Missing' : ($writable ? 'Writable' : 'Not writable'),
                'passes' => $exists && $writable,
                'help' => 'The installer must be able to write to '.str_replace(base_path().DIRECTORY_SEPARATOR, '', $requirement['path']).'.',
            ];
        })->all();
    }
}