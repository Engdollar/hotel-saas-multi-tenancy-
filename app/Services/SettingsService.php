<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\TemplatePreset;
use App\Models\ThemePreset;
use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SettingsService
{
    public const DEFAULT_THEME_PRESET = 'cleopatra';

    public const THEME_SYNC_TEMPLATE_SLUG = 'match-selected-theme';

    public const PDF_HEADER_TEMPLATE_TYPE = 'pdf_header';

    public const EXCEL_HEADER_TEMPLATE_TYPE = 'excel_header';

    public const EMAIL_TEMPLATE_TYPE = 'email';

    public const THEME_PRESETS = [
        'cleopatra' => [
            'name' => 'Crimson Veil',
            'description' => 'Soft rose neutrals with wine-red depth and warm crimson highlights.',
            'swatches' => ['#f4e8e9', '#d8b8bc', '#3a2328', '#b54c5a'],
        ],
        'lagoon' => [
            'name' => 'Lagoon Mist',
            'description' => 'Soft blue-gray panels with a teal accent system.',
            'swatches' => ['#e8eef0', '#b6c9cd', '#26353a', '#3d8f8d'],
        ],
        'ember' => [
            'name' => 'Ember Smoke',
            'description' => 'Dusty rose-gray layers with softened plum contrast for long sessions.',
            'swatches' => ['#eee7e6', '#cbbab8', '#342d31', '#9a6f79'],
        ],
        'midnight' => [
            'name' => 'Midnight Signal',
            'description' => 'Deep graphite with electric cyan accents.',
            'swatches' => ['#1a2230', '#2f4259', '#d9e4f1', '#4ab6ff'],
        ],
        'sakura' => [
            'name' => 'Slate Brass',
            'description' => 'Cool mist neutrals with steel blue depth and warm brass contrast.',
            'swatches' => ['#E8EDF2', '#2C3947', '#547A95', '#C2A56D'],
        ],
        'citrus' => [
            'name' => 'Citrus Field',
            'description' => 'Soft lime neutrals with herbal green highlights.',
            'swatches' => ['#f3f6e8', '#d7e09e', '#313724', '#aab53e'],
        ],
    ];

    public const THEME_MODES = [
        'light' => 'Light',
        'dark' => 'Dark',
        'system' => 'System',
    ];

    public const TEMPLATE_PRESETS = [
        self::PDF_HEADER_TEMPLATE_TYPE => [
            'executive-crimson' => [
                'name' => 'Executive Crimson',
                'description' => 'A rich brand panel with confident crimson accents for formal exports.',
                'swatches' => ['#9d3948', '#c95b69', '#f8fafc', '#eef2f7'],
                'content' => [
                    'kicker' => '{{ project_title }}',
                    'title' => '{{ export_title }}',
                    'subtitle' => '{{ export_subtitle }}',
                    'accent_start' => '#9d3948',
                    'accent_end' => '#c95b69',
                    'surface' => '#f8fafc',
                    'border' => '#d7dce5',
                    'text_primary' => '#18212f',
                    'text_muted' => '#617084',
                    'heading_background' => '#eef2f7',
                    'heading_text' => '#455468',
                ],
            ],
            'scholastic-slate' => [
                'name' => 'Scholastic Slate',
                'description' => 'A cooler academic header with muted slate and brass-inspired structure.',
                'swatches' => ['#35516a', '#8ca7bf', '#f4f6f8', '#e2e8ef'],
                'content' => [
                    'kicker' => '{{ project_title }} archive',
                    'title' => '{{ export_title }}',
                    'subtitle' => '{{ export_subtitle }}',
                    'accent_start' => '#35516a',
                    'accent_end' => '#8ca7bf',
                    'surface' => '#f4f6f8',
                    'border' => '#d5dde6',
                    'text_primary' => '#18293a',
                    'text_muted' => '#5f7285',
                    'heading_background' => '#e2e8ef',
                    'heading_text' => '#35516a',
                ],
            ],
            'campus-parchment' => [
                'name' => 'Campus Parchment',
                'description' => 'A softer parchment export frame with ceremonial warmth and high legibility.',
                'swatches' => ['#8d6548', '#d4a373', '#fcf8f2', '#efe6db'],
                'content' => [
                    'kicker' => '{{ project_title }} records',
                    'title' => '{{ export_title }}',
                    'subtitle' => '{{ export_subtitle }}',
                    'accent_start' => '#8d6548',
                    'accent_end' => '#d4a373',
                    'surface' => '#fcf8f2',
                    'border' => '#eadccf',
                    'text_primary' => '#2f261f',
                    'text_muted' => '#746456',
                    'heading_background' => '#efe6db',
                    'heading_text' => '#6f4e37',
                ],
            ],
        ],
        self::EXCEL_HEADER_TEMPLATE_TYPE => [
            'crimson-ledger' => [
                'name' => 'Crimson Ledger',
                'description' => 'Strong branded title rows with a clean reporting grid for spreadsheets.',
                'swatches' => ['#9d3948', '#f7eef0', '#f2f4f7', '#455468'],
                'content' => [
                    'title' => '{{ export_title }}',
                    'subtitle' => '{{ export_subtitle }}',
                    'accent' => '#9d3948',
                    'title_text' => '#ffffff',
                    'meta_background' => '#f7eef0',
                    'meta_text' => '#6d4850',
                    'heading_background' => '#eef2f7',
                    'heading_text' => '#455468',
                    'body_border' => '#d7dce5',
                ],
            ],
            'ocean-sheet' => [
                'name' => 'Ocean Sheet',
                'description' => 'Cool blue reporting rows suited to operational and compliance exports.',
                'swatches' => ['#2d5f7a', '#edf4f8', '#dce8ef', '#3f5f70'],
                'content' => [
                    'title' => '{{ export_title }}',
                    'subtitle' => '{{ export_subtitle }}',
                    'accent' => '#2d5f7a',
                    'title_text' => '#ffffff',
                    'meta_background' => '#edf4f8',
                    'meta_text' => '#537286',
                    'heading_background' => '#dce8ef',
                    'heading_text' => '#2d5f7a',
                    'body_border' => '#cfd9e3',
                ],
            ],
            'bronze-register' => [
                'name' => 'Bronze Register',
                'description' => 'A warmer workbook header for admissions, finance, and audit handoffs.',
                'swatches' => ['#8d6548', '#fbf5ee', '#efe3d3', '#6f5644'],
                'content' => [
                    'title' => '{{ export_title }}',
                    'subtitle' => '{{ export_subtitle }}',
                    'accent' => '#8d6548',
                    'title_text' => '#ffffff',
                    'meta_background' => '#fbf5ee',
                    'meta_text' => '#7d6956',
                    'heading_background' => '#efe3d3',
                    'heading_text' => '#6f5644',
                    'body_border' => '#decfbe',
                ],
            ],
        ],
        self::EMAIL_TEMPLATE_TYPE => [
            'welcome-crimson' => [
                'name' => 'Welcome Crimson',
                'description' => 'A warm onboarding note for new staff, students, or administrators.',
                'swatches' => ['#b54c5a', '#fff6f7', '#3a2328', '#f3dadd'],
                'content' => [
                    'subject' => 'Welcome to {{ project_title }}',
                    'headline' => 'Your account is ready',
                    'greeting' => 'Hi {{ recipient_name }},',
                    'body_html' => '<p>Your access to {{ project_title }} is now active.</p><p>You can use the button below to sign in and continue your work.</p>',
                    'button_label' => 'Open workspace',
                    'signature' => 'Regards,<br>{{ project_title }} team',
                    'accent' => '#b54c5a',
                    'surface' => '#fff6f7',
                ],
            ],
            'alert-slate' => [
                'name' => 'Alert Slate',
                'description' => 'A restrained operational template for reminders, approvals, and alerts.',
                'swatches' => ['#35516a', '#f5f8fb', '#203142', '#dbe5ef'],
                'content' => [
                    'subject' => '{{ project_title }} update for {{ recipient_name }}',
                    'headline' => 'A new update needs your attention',
                    'greeting' => 'Hello {{ recipient_name }},',
                    'body_html' => '<p>There is a new update waiting for you in {{ project_title }}.</p><p>Review the latest activity and take any action required.</p>',
                    'button_label' => 'Review update',
                    'signature' => 'Thanks,<br>{{ project_title }} operations',
                    'accent' => '#35516a',
                    'surface' => '#f5f8fb',
                ],
            ],
            'campaign-gold' => [
                'name' => 'Campaign Gold',
                'description' => 'A brighter announcement template for launches, campaigns, and milestone messages.',
                'swatches' => ['#a46f2d', '#fffaf2', '#42311f', '#f0e0c5'],
                'content' => [
                    'subject' => 'News from {{ project_title }}',
                    'headline' => 'A new milestone is live',
                    'greeting' => 'Dear {{ recipient_name }},',
                    'body_html' => '<p>We have a new announcement to share from {{ project_title }}.</p><p>Open the workspace to see the latest rollout details and highlights.</p>',
                    'button_label' => 'View announcement',
                    'signature' => 'Warm regards,<br>{{ project_title }} communications',
                    'accent' => '#a46f2d',
                    'surface' => '#fffaf2',
                ],
            ],
        ],
    ];

    protected const THEME_TOKEN_KEYS = [
        'app-bg',
        'app-bg-gradient',
        'shell-surface',
        'sidebar-surface',
        'panel-bg',
        'panel-soft',
        'panel-border',
        'text-primary',
        'text-muted',
        'text-soft',
        'field-bg',
        'field-border',
        'field-focus',
        'accent',
        'accent-strong',
        'accent-contrast',
        'shadow-color',
    ];

    public function __construct(protected CurrentCompanyContext $companyContext)
    {
    }

    public function all(): Collection
    {
        $settings = collect($this->defaults());

        if (Schema::hasTable('settings')) {
            $settings = $settings->merge($this->settingsQuery()->pluck('value', 'key'));
        }

        $selectedPreset = (string) $settings->get('theme_preset', self::DEFAULT_THEME_PRESET);

        if (! $this->hasThemePreset($selectedPreset)) {
            $settings->put('theme_preset', $this->fallbackThemePreset());
        }

        return $settings;
    }

    public function update(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if ($value instanceof UploadedFile) {
                $value = $this->storeAsset($key, $value);
            }

            if (is_array($value)) {
                $value = json_encode($value, JSON_THROW_ON_ERROR);
            }

            $this->settingsQuery()->updateOrCreate(
                ['company_id' => $this->scopeCompanyId(), 'key' => $key],
                ['value' => $value],
            );
        }
    }

    public function value(string $key, ?string $default = null): ?string
    {
        return $this->settingsQuery()->where('key', $key)->value('value') ?? $default;
    }

    public function defaults(): array
    {
        return [
            'project_title' => config('app.name'),
            'tenancy_base_domain' => config('tenancy.base_domain', ''),
            'theme_preset' => self::DEFAULT_THEME_PRESET,
            'theme_mode' => 'dark',
            'dashboard_stats' => json_encode([], JSON_THROW_ON_ERROR),
            'dashboard_charts' => json_encode([], JSON_THROW_ON_ERROR),
            'auth_login_visual_mode' => 'default',
            'auth_register_visual_mode' => 'default',
            'pdf_header_template' => 'executive-crimson',
            'excel_header_template' => 'crimson-ledger',
            'email_template' => 'welcome-crimson',
        ];
    }

    public function themePresets(): array
    {
        return array_merge(self::THEME_PRESETS, $this->customThemePresets());
    }

    public function themeModes(): array
    {
        return self::THEME_MODES;
    }

    public function presetKeys(): array
    {
        return array_keys($this->themePresets());
    }

    public function pdfBranding(array $context = []): array
    {
        $settings = $this->all();
        $projectTitle = (string) $settings->get('project_title', config('app.name'));

        return [
            'project_title' => $projectTitle,
            'project_initials' => Str::of($projectTitle)
                ->explode(' ')
                ->filter()
                ->take(2)
                ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
                ->implode(''),
            'logo_data_uri' => $this->pdfLogoDataUri((string) $settings->get('logo', '')),
            'generated_at' => Date::now()->format('M d, Y g:i A'),
            'template' => $this->selectedTemplate(self::PDF_HEADER_TEMPLATE_TYPE, $context),
        ];
    }

    public function excelBranding(array $context = []): array
    {
        $settings = $this->all();
        $projectTitle = (string) $settings->get('project_title', config('app.name'));

        return array_merge($this->selectedTemplate(self::EXCEL_HEADER_TEMPLATE_TYPE, $context), [
            'project_title' => $projectTitle,
            'project_initials' => Str::of($projectTitle)
                ->explode(' ')
                ->filter()
                ->take(2)
                ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
                ->implode(''),
            'generated_at' => (string) ($context['generated_at'] ?? Date::now()->format('M d, Y g:i A')),
            'logo_path' => $this->excelLogoPath((string) $settings->get('logo', '')),
        ]);
    }

    public function emailBranding(array $context = []): array
    {
        return $this->selectedTemplate(self::EMAIL_TEMPLATE_TYPE, $context);
    }

    public function customPresetLibrary(): Collection
    {
        if (! Schema::hasTable('theme_presets')) {
            return collect();
        }

        return $this->themePresetQuery()->orderBy('name')->get();
    }

    public function hasThemePreset(?string $slug): bool
    {
        return $slug !== null && array_key_exists($slug, $this->themePresets());
    }

    public function templatePresets(string $type): array
    {
        $builtIn = self::TEMPLATE_PRESETS[$type] ?? [];

        if (! Schema::hasTable('template_presets')) {
            return $builtIn;
        }

        $overrides = $this->templatePresetQuery()
            ->where('type', $type)
            ->where('slug', '!=', self::THEME_SYNC_TEMPLATE_SLUG)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function (TemplatePreset $template) use ($builtIn) {
                $base = $builtIn[$template->slug] ?? [
                    'name' => $template->name,
                    'description' => $template->description,
                    'swatches' => [],
                    'content' => [],
                ];

                return [
                    $template->slug => [
                        'name' => $template->name ?: ($base['name'] ?? Str::headline(str_replace('-', ' ', $template->slug))),
                        'description' => $template->description ?: ($base['description'] ?? null),
                        'swatches' => $base['swatches'] ?? [],
                        'content' => array_replace($base['content'] ?? [], $template->content ?? []),
                    ],
                ];
            })
            ->all();

        $templates = array_replace($builtIn, $overrides);
        $themeLinkedPreset = $this->themeLinkedTemplatePreset($type);

        if ($themeLinkedPreset !== null) {
            $templates = [$themeLinkedPreset['slug'] => $themeLinkedPreset] + array_diff_key($templates, [$themeLinkedPreset['slug'] => true]);
        }

        return $templates;
    }

    public function templateKeys(string $type): array
    {
        return array_keys($this->templatePresets($type));
    }

    public function selectedTemplateSlug(string $type): string
    {
        $key = $this->templateSettingKey($type);
        $settings = $this->all();
        $slug = (string) $settings->get($key, $this->defaultTemplateSlug($type));
        $available = $this->templateKeys($type);

        return in_array($slug, $available, true)
            ? $slug
            : ($available[0] ?? $this->defaultTemplateSlug($type));
    }

    public function selectedTemplate(string $type, array $context = []): array
    {
        $slug = $this->selectedTemplateSlug($type);
        $templates = $this->templatePresets($type);
        $template = $templates[$slug] ?? reset($templates) ?: ['name' => 'Default', 'description' => null, 'swatches' => [], 'content' => []];

        return [
            'slug' => $slug,
            'name' => $template['name'],
            'description' => $template['description'] ?? null,
            'swatches' => $template['swatches'] ?? [],
            'content' => $this->renderTemplateContent($template['content'] ?? [], $context),
        ];
    }

    public function saveTemplateOverride(string $type, string $slug, array $attributes): TemplatePreset
    {
        if ($slug === self::THEME_SYNC_TEMPLATE_SLUG) {
            return new TemplatePreset([
                'type' => $type,
                'slug' => $slug,
                'name' => $attributes['name'] ?? 'Match Selected Theme',
                'description' => $attributes['description'] ?? null,
                'content' => $attributes['content'] ?? [],
            ]);
        }

        if (! Schema::hasTable('template_presets')) {
            throw new InvalidArgumentException('The template_presets table does not exist yet. Run php artisan migrate first.');
        }

        return $this->templatePresetQuery()->updateOrCreate(
            ['company_id' => $this->scopeCompanyId(), 'type' => $type, 'slug' => $slug],
            [
                'name' => (string) ($attributes['name'] ?? Str::headline(str_replace('-', ' ', $slug))),
                'description' => $attributes['description'] ?? null,
                'content' => $attributes['content'] ?? [],
            ],
        );
    }

    public function themePresetStyles(): string
    {
        if (! Schema::hasTable('theme_presets')) {
            return '';
        }

        return $this->themePresetQuery()
            ->orderBy('name')
            ->get()
            ->map(fn (ThemePreset $preset) => $this->renderThemePresetCss($preset))
            ->implode("\n\n");
    }

    public function saveThemePreset(array $attributes, bool $replace = false): ThemePreset
    {
        $slug = (string) ($attributes['slug'] ?? '');

        if ($slug === '') {
            throw new InvalidArgumentException('Each theme preset must have a slug.');
        }

        if (array_key_exists($slug, self::THEME_PRESETS)) {
            throw new InvalidArgumentException('Built-in presets cannot be overwritten. Use a different slug.');
        }

        if (! Schema::hasTable('theme_presets')) {
            throw new InvalidArgumentException('The theme_presets table does not exist yet. Run php artisan migrate first.');
        }

        $existing = $this->themePresetQuery()->where('slug', $slug)->first();

        if ($existing && ! $replace) {
            throw new InvalidArgumentException('This preset already exists. Re-run with --replace to overwrite it.');
        }

        return $this->themePresetQuery()->updateOrCreate(
            ['company_id' => $this->scopeCompanyId(), 'slug' => $slug],
            [
                'name' => $attributes['name'],
                'description' => $attributes['description'] ?? null,
                'keywords' => array_values($attributes['keywords'] ?? []),
                'swatches' => array_values($attributes['swatches'] ?? []),
                'light_tokens' => $this->filterThemeTokens($attributes['light_tokens'] ?? []),
                'dark_tokens' => $this->filterThemeTokens($attributes['dark_tokens'] ?? []),
                'is_generated' => (bool) ($attributes['is_generated'] ?? true),
            ],
        );
    }

    public function resetThemePresetSelection(array $deletedSlugs): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $currentPreset = $this->settingsQuery()->where('key', 'theme_preset')->value('value');

        if ($currentPreset && in_array($currentPreset, $deletedSlugs, true)) {
            $this->settingsQuery()->updateOrCreate(
                ['company_id' => $this->scopeCompanyId(), 'key' => 'theme_preset'],
                ['value' => $this->fallbackThemePreset()],
            );
        }
    }

    public function deleteThemePreset(ThemePreset $preset): void
    {
        $slug = $preset->slug;
        $preset->delete();
        $this->resetThemePresetSelection([$slug]);
    }

    public function duplicateThemePreset(ThemePreset $preset): ThemePreset
    {
        $name = $this->uniqueDuplicateName($preset->name);
        $slug = $this->uniqueSlug(Str::slug($name));

        return $this->saveThemePreset([
            'slug' => $slug,
            'name' => $name,
            'description' => $preset->description,
            'keywords' => $preset->keywords ?? [],
            'swatches' => $preset->swatches ?? [],
            'light_tokens' => $preset->light_tokens ?? [],
            'dark_tokens' => $preset->dark_tokens ?? [],
            'is_generated' => $preset->is_generated,
        ], false);
    }

    public function exportThemePresetPack(array $slugs = [], bool $includeBuiltIn = false): array
    {
        $presets = collect();

        if ($includeBuiltIn) {
            $presets = $presets->merge(collect(self::THEME_PRESETS)->map(function (array $preset, string $slug) {
                return [
                    'slug' => $slug,
                    'name' => $preset['name'],
                    'description' => $preset['description'] ?? null,
                    'keywords' => [],
                    'swatches' => $preset['swatches'] ?? [],
                    'light_tokens' => [],
                    'dark_tokens' => [],
                    'is_generated' => false,
                    'source' => 'built-in',
                ];
            })->values());
        }

        if (Schema::hasTable('theme_presets')) {
            $customPresets = $this->themePresetQuery()
                ->when($slugs !== [], fn ($query) => $query->whereIn('slug', $slugs))
                ->orderBy('name')
                ->get()
                ->map(fn (ThemePreset $preset) => [
                    'slug' => $preset->slug,
                    'name' => $preset->name,
                    'description' => $preset->description,
                    'keywords' => $preset->keywords ?? [],
                    'swatches' => $preset->swatches ?? [],
                    'light_tokens' => $preset->light_tokens ?? [],
                    'dark_tokens' => $preset->dark_tokens ?? [],
                    'is_generated' => $preset->is_generated,
                    'source' => 'custom',
                ]);

            $presets = $presets->merge($customPresets);
        }

        return [
            'generated_at' => now()->toIso8601String(),
            'project' => config('app.name'),
            'presets' => $presets->values()->all(),
        ];
    }

    public function importThemePresetPack(array $payload, bool $replace = false): array
    {
        if (! isset($payload['presets']) || ! is_array($payload['presets'])) {
            throw new InvalidArgumentException('The preset pack must contain a presets array.');
        }

        $imported = 0;
        $skipped = [];

        foreach ($payload['presets'] as $preset) {
            $slug = (string) ($preset['slug'] ?? '');

            if ($slug === '' || array_key_exists($slug, self::THEME_PRESETS)) {
                $skipped[] = $slug !== '' ? $slug : 'unknown';
                continue;
            }

            try {
                $this->saveThemePreset([
                    'slug' => $slug,
                    'name' => $preset['name'] ?? Str::headline(str_replace('-', ' ', $slug)),
                    'description' => $preset['description'] ?? null,
                    'keywords' => $preset['keywords'] ?? [],
                    'swatches' => $preset['swatches'] ?? [],
                    'light_tokens' => $preset['light_tokens'] ?? [],
                    'dark_tokens' => $preset['dark_tokens'] ?? [],
                    'is_generated' => (bool) ($preset['is_generated'] ?? true),
                ], $replace);
                $imported++;
            } catch (InvalidArgumentException) {
                $skipped[] = $slug;
            }
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
        ];
    }

    protected function storeAsset(string $key, UploadedFile $file): string
    {
        $currentPath = $this->value($key);

        if ($currentPath) {
            Storage::disk('public')->delete($currentPath);
        }

        $scope = $this->companyContext->id() !== null
            ? 'company-'.$this->companyContext->id()
            : 'global';

        return $file->store('branding/'.$scope, 'public');
    }

    protected function customThemePresets(): array
    {
        if (! Schema::hasTable('theme_presets')) {
            return [];
        }

        return $this->themePresetQuery()
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (ThemePreset $preset) => [
                $preset->slug => [
                    'name' => $preset->name,
                    'description' => $preset->description,
                    'swatches' => $preset->swatches,
                ],
            ])
            ->all();
    }

    protected function fallbackThemePreset(): string
    {
        $presetKeys = $this->presetKeys();

        return in_array(self::DEFAULT_THEME_PRESET, $presetKeys, true)
            ? self::DEFAULT_THEME_PRESET
            : ($presetKeys[0] ?? self::DEFAULT_THEME_PRESET);
    }

    protected function renderThemePresetCss(ThemePreset $preset): string
    {
        return implode("\n", [
            "html[data-theme-preset='{$preset->slug}'] {",
            $this->renderCssVariables($preset->light_tokens),
            '}',
            '',
            "html.dark[data-theme-preset='{$preset->slug}'] {",
            $this->renderCssVariables($preset->dark_tokens),
            '}',
        ]);
    }

    protected function renderCssVariables(array $tokens): string
    {
        return collect($this->filterThemeTokens($tokens))
            ->map(fn (string $value, string $key) => "        --{$key}: {$value};")
            ->implode("\n");
    }

    protected function filterThemeTokens(array $tokens): array
    {
        return collect(self::THEME_TOKEN_KEYS)
            ->filter(fn (string $key) => array_key_exists($key, $tokens))
            ->mapWithKeys(fn (string $key) => [$key => (string) $tokens[$key]])
            ->all();
    }

    protected function pdfLogoDataUri(string $logoPath): ?string
    {
        if ($logoPath === '' || ! Storage::disk('public')->exists($logoPath)) {
            return null;
        }

        $extension = Str::lower(pathinfo($logoPath, PATHINFO_EXTENSION));
        $mimeType = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => 'image/png',
        };
        $contents = Storage::disk('public')->get($logoPath);

        return 'data:'.$mimeType.';base64,'.base64_encode($contents);
    }

    protected function excelLogoPath(string $logoPath): ?string
    {
        if ($logoPath === '' || ! Storage::disk('public')->exists($logoPath)) {
            return null;
        }

        $extension = Str::lower(pathinfo($logoPath, PATHINFO_EXTENSION));

        if (! in_array($extension, ['png', 'jpg', 'jpeg', 'gif'], true)) {
            return null;
        }

        return Storage::disk('public')->path($logoPath);
    }

    protected function uniqueDuplicateName(string $baseName): string
    {
        $attempt = $baseName.' Copy';
        $suffix = 2;

        while ($this->customPresetLibrary()->contains(fn (ThemePreset $preset) => $preset->name === $attempt)) {
            $attempt = $baseName.' Copy '.$suffix;
            $suffix++;
        }

        return $attempt;
    }

    protected function uniqueSlug(string $baseSlug): string
    {
        $slug = $baseSlug;
        $suffix = 2;

        while ($this->hasThemePreset($slug)) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    protected function templateSettingKey(string $type): string
    {
        return match ($type) {
            self::PDF_HEADER_TEMPLATE_TYPE => 'pdf_header_template',
            self::EXCEL_HEADER_TEMPLATE_TYPE => 'excel_header_template',
            self::EMAIL_TEMPLATE_TYPE => 'email_template',
            default => throw new InvalidArgumentException('Unsupported template type.'),
        };
    }

    protected function defaultTemplateSlug(string $type): string
    {
        return match ($type) {
            self::PDF_HEADER_TEMPLATE_TYPE => 'executive-crimson',
            self::EXCEL_HEADER_TEMPLATE_TYPE => 'crimson-ledger',
            self::EMAIL_TEMPLATE_TYPE => 'welcome-crimson',
            default => throw new InvalidArgumentException('Unsupported template type.'),
        };
    }

    protected function renderTemplateContent(array $content, array $context = []): array
    {
        $replacements = $this->templateReplacements($context);

        return collect($content)
            ->map(fn ($value) => is_string($value) ? strtr($value, $replacements) : $value)
            ->all();
    }

    protected function templateReplacements(array $context = []): array
    {
        $settings = $this->all();
        $projectTitle = (string) $settings->get('project_title', config('app.name'));

        return collect([
            '{{ project_title }}' => $projectTitle,
            '{{ export_title }}' => (string) ($context['export_title'] ?? 'Export'),
            '{{ export_subtitle }}' => (string) ($context['export_subtitle'] ?? 'Operational export'),
            '{{ export_generated_at }}' => (string) ($context['generated_at'] ?? Date::now()->format('M d, Y g:i A')),
            '{{ recipient_name }}' => (string) ($context['recipient_name'] ?? 'Recipient'),
            '{{ action_url }}' => (string) ($context['action_url'] ?? url('/login')),
            '{{ support_email }}' => (string) ($context['support_email'] ?? config('mail.from.address', 'support@example.com')),
        ])->merge(
            collect($context)
                ->filter(fn ($value) => is_scalar($value))
                ->mapWithKeys(fn ($value, $key) => ['{{ '.$key.' }}' => (string) $value])
        )->all();
    }

    protected function themeLinkedTemplatePreset(string $type): ?array
    {
        $themePreset = $this->selectedThemePreset();

        if ($themePreset === null) {
            return null;
        }

        $swatches = collect($themePreset['swatches'] ?? [])
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->values()
            ->all();

        if ($swatches === []) {
            return null;
        }

        $accent = $swatches[3] ?? end($swatches) ?: '#b54c5a';
        $accentSoft = $swatches[1] ?? $swatches[0] ?? $accent;
        $surface = $this->lightestColor($swatches);
        $surfaceSoft = $this->nextLightestColor($swatches, $surface);
        $textPrimary = $this->darkestColor($swatches);
        $textMuted = $this->nextDarkestColor($swatches, $textPrimary);
        $contrastText = $this->contrastTextColor($accent);

        return match ($type) {
            self::PDF_HEADER_TEMPLATE_TYPE => [
                'slug' => self::THEME_SYNC_TEMPLATE_SLUG,
                'name' => 'Match Selected Theme',
                'description' => 'Uses the current '.$themePreset['name'].' theme preset for the PDF export header.',
                'swatches' => [$accent, $accentSoft, $surface, $surfaceSoft],
                'content' => [
                    'kicker' => '{{ project_title }}',
                    'title' => '{{ export_title }}',
                    'subtitle' => '{{ export_subtitle }}',
                    'accent_start' => $accent,
                    'accent_end' => $accentSoft,
                    'surface' => $surface,
                    'border' => $surfaceSoft,
                    'text_primary' => $textPrimary,
                    'text_muted' => $textMuted,
                    'heading_background' => $surfaceSoft,
                    'heading_text' => $textPrimary,
                ],
            ],
            self::EXCEL_HEADER_TEMPLATE_TYPE => [
                'slug' => self::THEME_SYNC_TEMPLATE_SLUG,
                'name' => 'Match Selected Theme',
                'description' => 'Uses the current '.$themePreset['name'].' theme preset for the Excel export header.',
                'swatches' => [$accent, $surface, $surfaceSoft, $textPrimary],
                'content' => [
                    'title' => '{{ export_title }}',
                    'subtitle' => '{{ export_subtitle }}',
                    'accent' => $accent,
                    'title_text' => $contrastText,
                    'meta_background' => $surface,
                    'meta_text' => $textMuted,
                    'heading_background' => $surfaceSoft,
                    'heading_text' => $textPrimary,
                    'body_border' => $surfaceSoft,
                ],
            ],
            self::EMAIL_TEMPLATE_TYPE => [
                'slug' => self::THEME_SYNC_TEMPLATE_SLUG,
                'name' => 'Match Selected Theme',
                'description' => 'Uses the current '.$themePreset['name'].' theme preset for the email template palette.',
                'swatches' => [$accent, $surface, $textPrimary, $surfaceSoft],
                'content' => [
                    'subject' => 'Welcome to {{ project_title }}',
                    'headline' => 'Your account is ready',
                    'greeting' => 'Hi {{ recipient_name }},',
                    'body_html' => '<p>Your access to {{ project_title }} is now active.</p><p>You can use the button below to continue your work.</p>',
                    'button_label' => 'Open workspace',
                    'signature' => 'Regards,<br>{{ project_title }} team',
                    'accent' => $accent,
                    'surface' => $surface,
                ],
            ],
            default => null,
        };
    }

    protected function selectedThemePreset(): ?array
    {
        $settings = $this->all();
        $slug = (string) $settings->get('theme_preset', self::DEFAULT_THEME_PRESET);
        $presets = $this->themePresets();

        return $presets[$slug] ?? null;
    }

    protected function lightestColor(array $colors): string
    {
        return collect($colors)
            ->sortByDesc(fn (string $color) => $this->colorLuminance($color))
            ->first() ?? '#f8fafc';
    }

    protected function nextLightestColor(array $colors, string $exclude): string
    {
        return collect($colors)
            ->reject(fn (string $color) => Str::lower($color) === Str::lower($exclude))
            ->sortByDesc(fn (string $color) => $this->colorLuminance($color))
            ->first() ?? $exclude;
    }

    protected function darkestColor(array $colors): string
    {
        return collect($colors)
            ->sortBy(fn (string $color) => $this->colorLuminance($color))
            ->first() ?? '#18212f';
    }

    protected function nextDarkestColor(array $colors, string $exclude): string
    {
        return collect($colors)
            ->reject(fn (string $color) => Str::lower($color) === Str::lower($exclude))
            ->sortBy(fn (string $color) => $this->colorLuminance($color))
            ->first() ?? $exclude;
    }

    protected function contrastTextColor(string $background): string
    {
        return $this->colorLuminance($background) > 0.5 ? '#18212f' : '#ffffff';
    }

    protected function colorLuminance(string $hex): float
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = collect(str_split($hex))->map(fn (string $part) => $part.$part)->implode('');
        }

        if (strlen($hex) !== 6) {
            return 0.5;
        }

        $red = hexdec(substr($hex, 0, 2));
        $green = hexdec(substr($hex, 2, 2));
        $blue = hexdec(substr($hex, 4, 2));

        return ((0.299 * $red) + (0.587 * $green) + (0.114 * $blue)) / 255;
    }

    protected function scopeCompanyId(): ?int
    {
        return $this->companyContext->id();
    }

    protected function settingsQuery()
    {
        return Setting::withoutGlobalScopes()->where('company_id', $this->scopeCompanyId());
    }

    protected function themePresetQuery()
    {
        return ThemePreset::withoutGlobalScopes()->where('company_id', $this->scopeCompanyId());
    }

    protected function templatePresetQuery()
    {
        return TemplatePreset::withoutGlobalScopes()->where('company_id', $this->scopeCompanyId());
    }
}