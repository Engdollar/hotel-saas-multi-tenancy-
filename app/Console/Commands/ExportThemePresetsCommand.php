<?php

namespace App\Console\Commands;

use App\Services\SettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportThemePresetsCommand extends Command
{
    protected $signature = 'theme:export-presets
                            {path? : Destination JSON file path}
                            {--slugs= : Comma-separated custom preset slugs to export}
                            {--include-built-in : Include the built-in presets from code}';

    protected $description = 'Export theme presets to a JSON preset pack.';

    public function handle(SettingsService $settingsService): int
    {
        $path = $this->argument('path') ?: base_path('storage/app/theme-preset-pack.json');
        $slugs = collect(preg_split('/\s*,\s*/', (string) $this->option('slugs')) ?: [])
            ->filter()
            ->values()
            ->all();

        $payload = $settingsService->exportThemePresetPack($slugs, (bool) $this->option('include-built-in'));

        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->components->info('Exported '.count($payload['presets']).' preset(s) to '.$path);

        return self::SUCCESS;
    }
}