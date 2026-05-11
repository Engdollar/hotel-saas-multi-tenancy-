<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ThemePresetGeneratorService
{
    protected const KEYWORD_PACKS = [
        'campus' => [
            'label' => 'Campus',
            'description' => 'Academic tones with calm blues, heritage neutrals, and polished brass accents.',
            'keywords' => ['academic', 'slate', 'soft', 'luxury'],
        ],
        'luxury' => [
            'label' => 'Luxury',
            'description' => 'Velvet contrast with premium warm accents and refined neutrals.',
            'keywords' => ['luxury', 'velvet', 'stone', 'dark'],
        ],
        'fintech' => [
            'label' => 'Fintech',
            'description' => 'Trust-heavy blue and cyan system colors with clean modern depth.',
            'keywords' => ['blue', 'cyan', 'minimal', 'dark'],
        ],
        'health' => [
            'label' => 'Health',
            'description' => 'Fresh green surfaces with soft contrast for calm, high-legibility screens.',
            'keywords' => ['green', 'emerald', 'light', 'soft'],
        ],
        'sunset' => [
            'label' => 'Sunset',
            'description' => 'Orange, amber, and rose light with warm energetic gradients.',
            'keywords' => ['sunset', 'amber', 'rose', 'soft'],
        ],
        'royal-red' => [
            'label' => 'Royal Red',
            'description' => 'Crimson-led identity with luxury warmth and darker ceremonial depth.',
            'keywords' => ['red', 'crimson', 'royal', 'luxury'],
        ],
        'deep-ocean' => [
            'label' => 'Deep Ocean',
            'description' => 'Oceanic blues and teals with darker naval foundations.',
            'keywords' => ['ocean', 'navy', 'teal', 'dark'],
        ],
    ];

    protected const VARIABLE_KEYS = [
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

    protected const KEYWORD_PROFILES = [
        'red' => ['hue' => 4, 'saturation' => 14, 'lightness' => 0, 'accent_offset' => 6],
        'crimson' => ['hue' => 352, 'saturation' => 16, 'lightness' => -2, 'accent_offset' => 10],
        'rose' => ['hue' => 340, 'saturation' => 10, 'lightness' => 4, 'accent_offset' => 8],
        'pink' => ['hue' => 328, 'saturation' => 12, 'lightness' => 6, 'accent_offset' => 10],
        'orange' => ['hue' => 24, 'saturation' => 14, 'lightness' => 2, 'accent_offset' => -6],
        'amber' => ['hue' => 39, 'saturation' => 11, 'lightness' => 3, 'accent_offset' => -8],
        'gold' => ['hue' => 44, 'saturation' => 10, 'lightness' => 2, 'accent_offset' => -12],
        'yellow' => ['hue' => 54, 'saturation' => 8, 'lightness' => 6, 'accent_offset' => -16],
        'green' => ['hue' => 132, 'saturation' => 10, 'lightness' => 0, 'accent_offset' => -10],
        'emerald' => ['hue' => 152, 'saturation' => 12, 'lightness' => -1, 'accent_offset' => -12],
        'teal' => ['hue' => 176, 'saturation' => 10, 'lightness' => -1, 'accent_offset' => -10],
        'cyan' => ['hue' => 192, 'saturation' => 12, 'lightness' => 2, 'accent_offset' => -6],
        'blue' => ['hue' => 214, 'saturation' => 12, 'lightness' => -1, 'accent_offset' => -4],
        'navy' => ['hue' => 224, 'saturation' => 8, 'lightness' => -6, 'accent_offset' => 12],
        'indigo' => ['hue' => 238, 'saturation' => 12, 'lightness' => -4, 'accent_offset' => 14],
        'violet' => ['hue' => 268, 'saturation' => 10, 'lightness' => -2, 'accent_offset' => 12],
        'purple' => ['hue' => 282, 'saturation' => 10, 'lightness' => -2, 'accent_offset' => 14],
        'plum' => ['hue' => 304, 'saturation' => 8, 'lightness' => -4, 'accent_offset' => 16],
        'stone' => ['hue' => 28, 'saturation' => -6, 'lightness' => 2, 'accent_offset' => 0],
        'slate' => ['hue' => 214, 'saturation' => -8, 'lightness' => -2, 'accent_offset' => 6],
        'charcoal' => ['hue' => 222, 'saturation' => -10, 'lightness' => -8, 'accent_offset' => 10],
        'forest' => ['hue' => 136, 'saturation' => 4, 'lightness' => -2, 'accent_offset' => -8],
        'ocean' => ['hue' => 202, 'saturation' => 8, 'lightness' => -1, 'accent_offset' => -5],
        'sunset' => ['hue' => 18, 'saturation' => 14, 'lightness' => 4, 'accent_offset' => 10],
        'desert' => ['hue' => 34, 'saturation' => 4, 'lightness' => 6, 'accent_offset' => -14],
        'royal' => ['hue' => 248, 'saturation' => 8, 'lightness' => -4, 'accent_offset' => 18],
        'luxury' => ['hue' => 28, 'saturation' => 6, 'lightness' => 0, 'accent_offset' => 18],
        'academic' => ['hue' => 214, 'saturation' => -2, 'lightness' => -1, 'accent_offset' => 8],
        'minimal' => ['hue' => 210, 'saturation' => -10, 'lightness' => 4, 'accent_offset' => 0],
        'soft' => ['hue' => 0, 'saturation' => -6, 'lightness' => 8, 'accent_offset' => 2],
        'dark' => ['hue' => 0, 'saturation' => 0, 'lightness' => -10, 'accent_offset' => 0],
        'light' => ['hue' => 0, 'saturation' => -2, 'lightness' => 10, 'accent_offset' => 0],
    ];

    protected const LEAD_WORDS = ['Velvet', 'Signal', 'Atlas', 'Glass', 'Harbor', 'Aurora', 'Summit', 'Orbit', 'Loom', 'Foundry', 'Crown', 'Ember'];

    protected const TRAIL_WORDS = ['Mist', 'Pulse', 'Canvas', 'Vault', 'Drift', 'Thread', 'Studio', 'Current', 'Beacon', 'Grove', 'Flare', 'Field'];

    public function generate(array $keywords, int $count = 50, array $packs = []): array
    {
        $normalizedKeywords = $this->expandKeywords($keywords, $packs);

        if ($normalizedKeywords === []) {
            return [];
        }

        $count = max(1, min($count, 100));
        $profile = $this->resolveProfile($normalizedKeywords);
        $seedPhrase = implode(' ', $normalizedKeywords);

        return collect(range(1, $count))
            ->map(fn (int $position) => $this->buildPreset($normalizedKeywords, $profile, $seedPhrase, $position))
            ->all();
    }

    public function keywordPacks(): array
    {
        return self::KEYWORD_PACKS;
    }

    public function expandKeywords(array $keywords, array $packs = []): array
    {
        $packKeywords = collect($packs)
            ->map(fn (string $pack) => self::KEYWORD_PACKS[$pack]['keywords'] ?? [])
            ->flatten();

        return collect($keywords)
            ->merge($packKeywords)
            ->map(fn (string $keyword) => Str::of($keyword)->trim()->lower()->replaceMatches('/[^a-z0-9\-\s]+/', '')->squish()->value())
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function buildPreset(array $keywords, array $profile, string $seedPhrase, int $position): array
    {
        $hueShift = ((($position - 1) * 9) % 48) - 24;
        $seedHue = $this->hashNumber($seedPhrase.'-hue-'.$position, -6, 6);
        $baseHue = $this->wrapHue($profile['hue'] + $hueShift + $seedHue);
        $accentHue = $this->wrapHue($baseHue + $profile['accent_offset'] + $this->hashNumber($seedPhrase.'-accent-'.$position, -10, 10));
        $neutralHue = $this->wrapHue($baseHue + $this->hashNumber($seedPhrase.'-neutral-'.$position, -8, 8));

        $lightSaturation = $this->clamp(10 + $profile['saturation'], 6, 30);
        $darkSaturation = $this->clamp(18 + $profile['saturation'], 10, 38);
        $lightnessBias = $profile['lightness'];

        $lightTextPrimary = $this->hslToHex($this->wrapHue($baseHue + 4), $this->clamp(18 + intdiv($profile['saturation'], 2), 10, 28), $this->clamp(18 + intdiv($lightnessBias, 3), 14, 26));
        $lightTextMuted = $this->hslToHex($this->wrapHue($baseHue + 2), $this->clamp(14 + intdiv($profile['saturation'], 3), 8, 20), $this->clamp(42 + intdiv($lightnessBias, 2), 34, 52));
        $lightTextSoft = $this->hslToHex($this->wrapHue($baseHue + 2), $this->clamp(12 + intdiv($profile['saturation'], 4), 6, 18), $this->clamp(58 + intdiv($lightnessBias, 2), 48, 68));
        $lightAccent = $this->hslToHex($accentHue, $this->clamp(56 + $profile['saturation'], 46, 76), $this->clamp(48 + intdiv($lightnessBias, 3), 38, 58));
        $lightAccentStrong = $this->hslToHex($accentHue, $this->clamp(60 + $profile['saturation'], 50, 82), $this->clamp(38 + intdiv($lightnessBias, 4), 28, 48));
        $lightAppBg = $this->hslToHex($neutralHue, $lightSaturation, $this->clamp(94 + intdiv($lightnessBias, 2), 88, 98));
        $lightShellSurface = $this->hexToRgba($this->hslToHex($neutralHue, $this->clamp($lightSaturation + 2, 8, 26), $this->clamp(97 + intdiv($lightnessBias, 3), 92, 99)), 0.84);
        $lightSidebarSurface = $this->hexToRgba($this->hslToHex($neutralHue, $this->clamp($lightSaturation + 1, 8, 24), $this->clamp(95 + intdiv($lightnessBias, 3), 90, 98)), 0.92);
        $lightPanelBg = $this->hexToRgba($this->hslToHex($neutralHue, $this->clamp($lightSaturation, 6, 22), $this->clamp(98 + intdiv($lightnessBias, 4), 94, 100)), 0.90);
        $lightPanelSoft = $this->hexToRgba($this->hslToHex($neutralHue, $this->clamp($lightSaturation - 2, 4, 18), $this->clamp(99 + intdiv($lightnessBias, 4), 95, 100)), 0.74);
        $lightFieldBg = $this->hexToRgba($this->hslToHex($neutralHue, $this->clamp($lightSaturation - 2, 4, 18), 99), 0.96);

        $darkTextPrimary = $this->hslToHex($this->wrapHue($baseHue + 4), $this->clamp(28 + intdiv($profile['saturation'], 3), 20, 36), $this->clamp(92 + intdiv($lightnessBias, 6), 86, 96));
        $darkTextMuted = $this->hslToHex($this->wrapHue($baseHue + 6), $this->clamp(22 + intdiv($profile['saturation'], 4), 14, 30), $this->clamp(72 + intdiv($lightnessBias, 6), 62, 78));
        $darkTextSoft = $this->hslToHex($this->wrapHue($baseHue + 6), $this->clamp(18 + intdiv($profile['saturation'], 5), 12, 24), $this->clamp(56 + intdiv($lightnessBias, 8), 48, 64));
        $darkAccent = $this->hslToHex($accentHue, $this->clamp(62 + $profile['saturation'], 50, 84), $this->clamp(60 + intdiv($lightnessBias, 5), 50, 68));
        $darkAccentStrong = $this->hslToHex($accentHue, $this->clamp(68 + $profile['saturation'], 56, 88), $this->clamp(50 + intdiv($lightnessBias, 5), 40, 58));
        $darkAppBgHex = $this->hslToHex($neutralHue, $darkSaturation, $this->clamp(10 + intdiv($lightnessBias, 4), 6, 18));
        $darkShellSurface = $this->hexToRgba($this->hslToHex($neutralHue, $this->clamp($darkSaturation + 2, 12, 40), $this->clamp(14 + intdiv($lightnessBias, 5), 9, 22)), 0.84);
        $darkSidebarSurface = $this->hexToRgba($this->hslToHex($neutralHue, $this->clamp($darkSaturation + 1, 12, 38), $this->clamp(11 + intdiv($lightnessBias, 5), 7, 19)), 0.94);
        $darkPanelBg = $this->hexToRgba($this->hslToHex($neutralHue, $this->clamp($darkSaturation, 10, 36), $this->clamp(15 + intdiv($lightnessBias, 5), 9, 22)), 0.92);
        $darkPanelSoft = $this->hexToRgba($this->hslToHex($neutralHue, $this->clamp($darkSaturation - 2, 8, 32), $this->clamp(19 + intdiv($lightnessBias, 5), 12, 26)), 0.74);
        $darkFieldBg = $this->hexToRgba($this->hslToHex($neutralHue, $this->clamp($darkSaturation - 2, 8, 32), $this->clamp(11 + intdiv($lightnessBias, 6), 7, 18)), 0.90);

        $name = $this->buildName($keywords, $position);
        $slug = Str::slug($name.'-'.$position);
        $keywordLabel = implode(', ', Arr::take($keywords, 3));

        return [
            'slug' => $slug,
            'name' => $name,
            'description' => 'Generated from '.$keywordLabel.' with a balanced dashboard-first contrast system.',
            'keywords' => $keywords,
            'swatches' => [$lightAppBg, $lightTextPrimary, $darkTextPrimary, $lightAccent],
            'light_tokens' => $this->normalizeTokens([
                'app-bg' => $lightAppBg,
                'app-bg-gradient' => sprintf(
                    'radial-gradient(circle at top left, %s, transparent 26%%), radial-gradient(circle at right 12%%, %s, transparent 28%%), linear-gradient(180deg, %s 0%%, %s 100%%)',
                    $this->hexToRgba($lightAccent, 0.14),
                    $this->hexToRgba($lightTextMuted, 0.10),
                    $this->hslToHex($neutralHue, $this->clamp($lightSaturation, 6, 24), $this->clamp(98 + intdiv($lightnessBias, 4), 94, 100)),
                    $lightAppBg,
                ),
                'shell-surface' => $lightShellSurface,
                'sidebar-surface' => $lightSidebarSurface,
                'panel-bg' => $lightPanelBg,
                'panel-soft' => $lightPanelSoft,
                'panel-border' => $this->hexToRgba($lightTextMuted, 0.14),
                'text-primary' => $lightTextPrimary,
                'text-muted' => $lightTextMuted,
                'text-soft' => $lightTextSoft,
                'field-bg' => $lightFieldBg,
                'field-border' => $this->hexToRgba($lightTextSoft, 0.18),
                'field-focus' => $this->hexToRgba($lightAccent, 0.18),
                'accent' => $lightAccent,
                'accent-strong' => $lightAccentStrong,
                'accent-contrast' => '#fff8f8',
                'shadow-color' => $this->hexToRgba($lightTextPrimary, 0.09),
            ]),
            'dark_tokens' => $this->normalizeTokens([
                'app-bg' => $darkAppBgHex,
                'app-bg-gradient' => sprintf(
                    'radial-gradient(circle at top left, %s, transparent 25%%), radial-gradient(circle at right 15%%, %s, transparent 26%%), linear-gradient(180deg, %s 0%%, %s 100%%)',
                    $this->hexToRgba($darkAccent, 0.16),
                    $this->hexToRgba($darkTextMuted, 0.16),
                    $this->hslToHex($neutralHue, $this->clamp($darkSaturation + 2, 12, 40), $this->clamp(13 + intdiv($lightnessBias, 5), 8, 20)),
                    $this->hslToHex($neutralHue, $this->clamp($darkSaturation, 10, 36), $this->clamp(8 + intdiv($lightnessBias, 5), 5, 16)),
                ),
                'shell-surface' => $darkShellSurface,
                'sidebar-surface' => $darkSidebarSurface,
                'panel-bg' => $darkPanelBg,
                'panel-soft' => $darkPanelSoft,
                'panel-border' => $this->hexToRgba($darkTextMuted, 0.14),
                'text-primary' => $darkTextPrimary,
                'text-muted' => $darkTextMuted,
                'text-soft' => $darkTextSoft,
                'field-bg' => $darkFieldBg,
                'field-border' => $this->hexToRgba($darkTextSoft, 0.22),
                'field-focus' => $this->hexToRgba($darkAccent, 0.20),
                'accent' => $darkAccent,
                'accent-strong' => $darkAccentStrong,
                'accent-contrast' => '#180c0f',
                'shadow-color' => 'rgba(0, 0, 0, 0.34)',
            ]),
        ];
    }

    protected function normalizeTokens(array $tokens): array
    {
        return collect(self::VARIABLE_KEYS)
            ->mapWithKeys(fn (string $key) => [$key => $tokens[$key]])
            ->all();
    }

    protected function resolveProfile(array $keywords): array
    {
        $matchedProfiles = collect();

        foreach ($keywords as $keyword) {
            $parts = preg_split('/[\s\-]+/', $keyword) ?: [];

            foreach ($parts as $part) {
                if (! isset(self::KEYWORD_PROFILES[$part])) {
                    continue;
                }

                $matchedProfiles->push(self::KEYWORD_PROFILES[$part]);
            }
        }

        if ($matchedProfiles->isEmpty()) {
            return [
                'hue' => $this->hashNumber(implode(' ', $keywords), 0, 359),
                'saturation' => 0,
                'lightness' => 0,
                'accent_offset' => 12,
            ];
        }

        $profile = [
            'hue' => (int) round($this->averageHue($matchedProfiles->pluck('hue')->all())),
            'saturation' => (int) round($matchedProfiles->sum('saturation')),
            'lightness' => (int) round($matchedProfiles->sum('lightness')),
            'accent_offset' => (int) round($matchedProfiles->avg('accent_offset')),
        ];

        $profile['saturation'] = $this->clamp($profile['saturation'], -12, 20);
        $profile['lightness'] = $this->clamp($profile['lightness'], -18, 18);
        $profile['accent_offset'] = $this->clamp($profile['accent_offset'], -24, 24);

        return $profile;
    }

    protected function buildName(array $keywords, int $position): string
    {
        $base = Str::of($keywords[($position - 1) % count($keywords)])->headline()->value();
        $lead = self::LEAD_WORDS[($position - 1) % count(self::LEAD_WORDS)];
        $trail = self::TRAIL_WORDS[($position - 1) % count(self::TRAIL_WORDS)];

        return trim($base.' '.$lead.' '.$trail);
    }

    protected function hslToHex(float $hue, float $saturation, float $lightness): string
    {
        $hue = $this->wrapHue($hue) / 360;
        $saturation = $this->clamp($saturation, 0, 100) / 100;
        $lightness = $this->clamp($lightness, 0, 100) / 100;

        if ($saturation == 0.0) {
            $red = $green = $blue = (int) round($lightness * 255);

            return sprintf('#%02x%02x%02x', $red, $green, $blue);
        }

        $q = $lightness < 0.5
            ? $lightness * (1 + $saturation)
            : $lightness + $saturation - ($lightness * $saturation);
        $p = (2 * $lightness) - $q;

        $red = $this->hueToRgb($p, $q, $hue + (1 / 3));
        $green = $this->hueToRgb($p, $q, $hue);
        $blue = $this->hueToRgb($p, $q, $hue - (1 / 3));

        return sprintf('#%02x%02x%02x', (int) round($red * 255), (int) round($green * 255), (int) round($blue * 255));
    }

    protected function hueToRgb(float $p, float $q, float $t): float
    {
        if ($t < 0) {
            $t += 1;
        }

        if ($t > 1) {
            $t -= 1;
        }

        if ($t < 1 / 6) {
            return $p + (($q - $p) * 6 * $t);
        }

        if ($t < 1 / 2) {
            return $q;
        }

        if ($t < 2 / 3) {
            return $p + (($q - $p) * ((2 / 3) - $t) * 6);
        }

        return $p;
    }

    protected function hexToRgba(string $hex, float $alpha): string
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = collect(str_split($hex))->map(fn (string $part) => $part.$part)->implode('');
        }

        $channels = sscanf($hex, '%02x%02x%02x');

        return sprintf('rgba(%d, %d, %d, %.2f)', $channels[0], $channels[1], $channels[2], $alpha);
    }

    protected function hashNumber(string $seed, int $min, int $max): int
    {
        if ($max <= $min) {
            return $min;
        }

        $value = hexdec(substr(md5($seed), 0, 8));

        return $min + ($value % (($max - $min) + 1));
    }

    protected function clamp(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }

    protected function wrapHue(float $value): float
    {
        $wrapped = fmod($value, 360);

        return $wrapped < 0 ? $wrapped + 360 : $wrapped;
    }

    protected function averageHue(array $hues): float
    {
        if ($hues === []) {
            return 0;
        }

        $x = 0.0;
        $y = 0.0;

        foreach ($hues as $hue) {
            $radians = deg2rad((float) $hue);
            $x += cos($radians);
            $y += sin($radians);
        }

        if ($x == 0.0 && $y == 0.0) {
            return 0;
        }

        return $this->wrapHue(rad2deg(atan2($y, $x)));
    }
}