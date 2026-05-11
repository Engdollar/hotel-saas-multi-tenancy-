@props(['items' => []])

@if (count($items))
    <nav {{ $attributes->merge(['class' => 'inline-flex flex-nowrap items-center gap-2 whitespace-nowrap text-sm text-muted']) }} style="display: inline-flex; align-items: center; flex-wrap: nowrap; white-space: nowrap;">
      
        @foreach ($items as $item)
            @if (! $loop->first)
                <span>/</span>
            @endif

            @if (! empty($item['url']))
                <a href="{{ $item['url'] }}" class="font-medium text-muted transition hover:opacity-80" style="color: var(--text-muted);">{{ $item['label'] }}</a>
            @else
                <span class="font-semibold" style="color: var(--text-primary);">{{ $item['label'] }}</span>
            @endif
        @endforeach
    </nav>
@endif