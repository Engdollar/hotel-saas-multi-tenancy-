<?php

namespace App\Console\Commands;

use App\Models\ThemePreset;
use App\Services\SettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class DeleteThemePresetCommand extends Command
{
    protected $signature = 'theme:delete-preset
                            {slugs?* : One or more saved custom preset slugs}
                            {--all : Delete all saved custom presets}
                            {--except= : Comma-separated slugs to keep when using --all}';

    protected $description = 'Delete saved custom theme presets from the database.';

    public function handle(SettingsService $settingsService): int
    {
        if (! Schema::hasTable('theme_presets')) {
            $this->components->error('The theme_presets table does not exist yet. Run php artisan migrate first.');

            return self::FAILURE;
        }

        $slugs = collect((array) $this->argument('slugs'));

        if ($this->option('all')) {
            $except = collect(preg_split('/\s*,\s*/', (string) $this->option('except')) ?: [])
                ->filter()
                ->values();

            $slugs = ThemePreset::query()
                ->when($except->isNotEmpty(), fn ($query) => $query->whereNotIn('slug', $except->all()))
                ->pluck('slug');
        }

        if ($slugs->isEmpty() && $this->input->isInteractive()) {
            $available = ThemePreset::query()->orderBy('name')->pluck('slug')->all();

            if ($available === []) {
                $this->components->info('There are no saved custom presets to delete.');

                return self::SUCCESS;
            }

            $response = $this->ask('Enter the slug list to delete, separated by commas', implode(', ', array_slice($available, 0, 3)));
            $slugs = collect(preg_split('/\s*,\s*/', (string) $response) ?: [])->filter()->values();
        }

        if ($slugs->isEmpty()) {
            $this->components->info('No custom presets were selected for deletion.');

            return self::SUCCESS;
        }

        $deletedSlugs = ThemePreset::query()
            ->whereIn('slug', $slugs->all())
            ->pluck('slug');

        if ($deletedSlugs->isEmpty()) {
            $this->components->warn('No matching saved custom presets were found.');

            return self::SUCCESS;
        }

        ThemePreset::query()->whereIn('slug', $deletedSlugs->all())->delete();
        $settingsService->resetThemePresetSelection($deletedSlugs->all());

        $this->components->info('Deleted '.count($deletedSlugs).' custom preset(s): '.$deletedSlugs->implode(', '));

        return self::SUCCESS;
    }
}