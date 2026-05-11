@php
    $projectTitle = $appSettings->get('project_title', config('app.name', 'Laravel'));
    $initial = strtoupper(mb_substr($projectTitle, 0, 1));
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex items-center justify-center']) }}>
    @if ($appSettings->get('logo'))
        <img src="{{ \App\Support\AssetPath::storageUrl($appSettings->get('logo')) }}" alt="{{ $projectTitle }}" class="h-11 w-11 rounded-2xl object-contain">
    @else
        <div class="flex h-11 w-11 items-center justify-center rounded-2xl text-lg font-black shadow-lg" style="background: linear-gradient(135deg, var(--accent) 0%, var(--accent-strong) 100%); color: var(--accent-contrast); box-shadow: 0 18px 34px -22px var(--shadow-color);">{{ $initial }}</div>
    @endif
</div>
