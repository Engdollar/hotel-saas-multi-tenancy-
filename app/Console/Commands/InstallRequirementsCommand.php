<?php

namespace App\Console\Commands;

use App\Services\InstallerRequirementsService;
use Illuminate\Console\Command;

class InstallRequirementsCommand extends Command
{
    protected $signature = 'install:requirements';

    protected $description = 'Check PHP runtime, required extensions, and writable paths for installation.';

    public function handle(InstallerRequirementsService $installerRequirementsService): int
    {
        $summary = $installerRequirementsService->summary();

        $rows = collect($summary['requirements'])
            ->map(fn (array $requirement) => [
                $requirement['category'],
                $requirement['label'],
                $requirement['expected'],
                $requirement['current'],
                $requirement['passes'] ? 'PASS' : 'FAIL',
            ])
            ->all();

        $this->table(['Category', 'Requirement', 'Expected', 'Current', 'Status'], $rows);

        if ($summary['passes']) {
            $this->components->info('All installation requirements are satisfied.');
            return self::SUCCESS;
        }

        $this->components->error('Some installation requirements are still failing. Review the table above before running the installer.');

        return self::FAILURE;
    }
}