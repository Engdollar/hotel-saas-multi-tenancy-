<?php

namespace App\Console\Commands;

use App\Services\SettingsService;
use App\Services\ThemePresetGeneratorService;
use Illuminate\Console\Command;
use Throwable;

class GenerateThemePresetsCommand extends Command
{
    protected $signature = 'theme:generate-presets
                            {keywords?* : Keywords that describe the theme direction}
                            {--count=50 : Number of candidate presets to generate (1-100)}
                            {--pack= : Comma-separated curated keyword pack keys}
                            {--save= : Comma-separated row numbers or slugs to save. Use all or none}
                            {--list-packs : Show the curated keyword packs and stop}
                            {--replace : Replace existing custom presets with the same slug}';

    protected $description = 'Generate theme preset candidates from keywords and optionally save the selected ones to the database.';

    public function handle(ThemePresetGeneratorService $generator, SettingsService $settingsService): int
    {
        if ($this->option('list-packs')) {
            $this->table(
                ['Pack', 'Keywords', 'Description'],
                collect($generator->keywordPacks())
                    ->map(fn (array $pack, string $key) => [$key, implode(', ', $pack['keywords']), $pack['description']])
                    ->values()
                    ->all(),
            );

            return self::SUCCESS;
        }

        $count = (int) $this->option('count');

        if ($count < 1 || $count > 100) {
            $this->components->error('The --count option must be between 1 and 100.');

            return self::FAILURE;
        }

        $packs = collect(preg_split('/\s*,\s*/', (string) $this->option('pack')) ?: [])->filter()->values()->all();
        $candidates = $generator->generate((array) $this->argument('keywords'), $count, $packs);

        if ($candidates === []) {
            $this->components->error('Provide at least one usable keyword or curated pack to generate presets.');

            return self::FAILURE;
        }

        $this->components->info('Generated '.count($candidates).' theme preset candidates.');

        $this->table(
            ['#', 'Name', 'Slug', 'Accent', 'Description'],
            collect($candidates)->map(fn (array $preset, int $index) => [
                $index + 1,
                $preset['name'],
                $preset['slug'],
                $preset['swatches'][3] ?? $preset['swatches'][0],
                $preset['description'],
            ])->all(),
        );

        $selection = $this->option('save');

        if ($selection === null && $this->input->isInteractive()) {
            $selection = $this->ask('Which presets should be saved? Enter row numbers, slugs, all, or none', 'none');
        }

        $selected = $this->resolveSelection($candidates, $selection);

        if ($selected === []) {
            $this->components->info('No presets were saved. Re-run with --save=1,4,8 or choose interactively when prompted.');

            return self::SUCCESS;
        }

        $saved = 0;

        foreach ($selected as $preset) {
            try {
                $settingsService->saveThemePreset($preset, (bool) $this->option('replace'));
                $saved++;
                $this->components->info('Saved '.$preset['slug']);
            } catch (Throwable $exception) {
                $this->components->warn($preset['slug'].': '.$exception->getMessage());
            }
        }

        $this->components->info('Saved '.$saved.' preset(s) to the database.');

        return self::SUCCESS;
    }

    protected function resolveSelection(array $candidates, mixed $selection): array
    {
        $value = is_string($selection) ? trim($selection) : '';

        if ($value === '' || strtolower($value) === 'none') {
            return [];
        }

        if (strtolower($value) === 'all') {
            return $candidates;
        }

        $lookup = collect($candidates)->mapWithKeys(fn (array $preset, int $index) => [
            (string) ($index + 1) => $preset,
            $preset['slug'] => $preset,
        ]);

        return collect(preg_split('/\s*,\s*/', $value) ?: [])
            ->filter()
            ->map(fn (string $token) => $lookup->get($token))
            ->filter()
            ->unique('slug')
            ->values()
            ->all();
    }
}