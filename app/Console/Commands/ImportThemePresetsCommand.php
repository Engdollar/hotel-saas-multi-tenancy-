<?php

namespace App\Console\Commands;

use App\Services\SettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

class ImportThemePresetsCommand extends Command
{
    protected $signature = 'theme:import-presets
                            {path : Source JSON preset pack file}
                            {--replace : Replace existing custom presets with matching slugs}';

    protected $description = 'Import theme presets from a JSON preset pack.';

    public function handle(SettingsService $settingsService): int
    {
        $path = (string) $this->argument('path');

        if (! File::exists($path)) {
            $this->components->error('Preset pack not found: '.$path);

            return self::FAILURE;
        }

        $payload = json_decode(File::get($path), true);

        if (! is_array($payload)) {
            $this->components->error('The preset pack is not valid JSON.');

            return self::FAILURE;
        }

        try {
            $result = $settingsService->importThemePresetPack($payload, (bool) $this->option('replace'));
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Imported '.$result['imported'].' preset(s).');

        if ($result['skipped'] !== []) {
            $this->components->warn('Skipped: '.implode(', ', $result['skipped']));
        }

        return self::SUCCESS;
    }
}