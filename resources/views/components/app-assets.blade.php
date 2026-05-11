@php
    $entrypoints = [
        'resources/css/app.css',
        'resources/js/app.js',
    ];

    $preferredBuildBase = \App\Support\AssetPath::buildBasePath();

    $candidateManifests = [
        ['file' => public_path('build/manifest.json'), 'base' => $preferredBuildBase],
        ['file' => public_path('public/build/manifest.json'), 'base' => 'build'],
    ];

    $resolvedManifest = null;

    foreach ($candidateManifests as $candidate) {
        if (is_file($candidate['file'])) {
            $content = @file_get_contents($candidate['file']);
            $decoded = is_string($content) ? json_decode($content, true) : null;

            if (is_array($decoded)) {
                $resolvedManifest = [
                    'base' => $candidate['base'],
                    'map' => $decoded,
                ];
                break;
            }
        }
    }

    $stylesheetUrls = [];
    $scriptUrls = [];

    if ($resolvedManifest !== null) {
        $manifest = $resolvedManifest['map'];
        $base = $resolvedManifest['base'];

        foreach ($entrypoints as $entrypoint) {
            $entry = $manifest[$entrypoint] ?? null;

            if (! is_array($entry)) {
                continue;
            }

            if (! empty($entry['file']) && str_ends_with((string) $entry['file'], '.css')) {
                $stylesheetUrls[] = asset(trim($base.'/'.ltrim((string) $entry['file'], '/'), '/'));
            }

            if (! empty($entry['css']) && is_array($entry['css'])) {
                foreach ($entry['css'] as $cssFile) {
                    $stylesheetUrls[] = asset(trim($base.'/'.ltrim((string) $cssFile, '/'), '/'));
                }
            }

            if (! empty($entry['file']) && str_ends_with((string) $entry['file'], '.js')) {
                $scriptUrls[] = asset(trim($base.'/'.ltrim((string) $entry['file'], '/'), '/'));
            }
        }

        $stylesheetUrls = array_values(array_unique($stylesheetUrls));
        $scriptUrls = array_values(array_unique($scriptUrls));
    }
@endphp

@if ($resolvedManifest !== null)
    @foreach ($stylesheetUrls as $href)
        <link rel="stylesheet" href="{{ $href }}">
    @endforeach

    @foreach ($scriptUrls as $src)
        <script type="module" src="{{ $src }}"></script>
    @endforeach
@else
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@endif
