@props([
    'user',
    'size' => 'h-11 w-11 rounded-2xl',
    'textSize' => 'text-base',
])

@if ($user->profile_image_path)
    <img src="{{ $user->profile_image_url }}" alt="{{ $user->name }}" class="{{ $size }} object-cover">
@else
    <div class="flex items-center justify-center bg-cyan-500 font-bold text-slate-950 {{ $size }} {{ $textSize }}">
        {{ $user->initials ?: 'U' }}
    </div>
@endif