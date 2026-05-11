<?php

namespace App\Console\Commands;

use App\Services\PermissionGeneratorService;
use Illuminate\Console\Command;

class SetupAdminCommand extends Command
{
    protected $signature = 'app:setup-admin {--refresh-passwords : Forward the password refresh option to system:setup}';

    protected $description = 'Compatibility alias for system:setup.';

    public function handle(PermissionGeneratorService $permissionGeneratorService): int
    {
        $this->call('system:setup', [
            '--refresh-passwords' => (bool) $this->option('refresh-passwords'),
        ]);

        return self::SUCCESS;
    }
}