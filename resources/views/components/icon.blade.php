@props(['name', 'class' => 'h-4 w-4'])

@switch($name)
    @case('plus')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" /></svg>
        @break
    @case('eye')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" /><circle cx="12" cy="12" r="3" /></svg>
        @break
    @case('eye-off')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" /><path stroke-linecap="round" stroke-linejoin="round" d="M10.6 10.7a3 3 0 0 0 4 4" /><path stroke-linecap="round" stroke-linejoin="round" d="M9.9 5.2A11 11 0 0 1 12 5c6 0 9.5 7 9.5 7a17.7 17.7 0 0 1-3.1 3.8" /><path stroke-linecap="round" stroke-linejoin="round" d="M6.2 6.3C4 7.8 2.5 12 2.5 12s3.5 7 9.5 7c1.6 0 3-.3 4.3-.9" /></svg>
        @break
    @case('pencil')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m15 5 4 4M4 20l4.5-1 10-10a2.12 2.12 0 0 0-3-3l-10 10L4 20Z" /></svg>
        @break
    @case('trash')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M9 7V4h6v3m-7 4v6m4-6v6m4-10-1 11a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L6 7" /></svg>
        @break
    @case('palette')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3a9 9 0 1 0 0 18h1a3 3 0 0 0 0-6h-.5a1.5 1.5 0 0 1 0-3H14a4 4 0 0 0 0-8h-2Z" /><circle cx="7.5" cy="11.5" r="1" fill="currentColor" stroke="none" /><circle cx="9.5" cy="7.5" r="1" fill="currentColor" stroke="none" /><circle cx="15.5" cy="7.5" r="1" fill="currentColor" stroke="none" /></svg>
        @break
    @case('moon')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20 15.5A8.5 8.5 0 0 1 8.5 4a8.5 8.5 0 1 0 11.5 11.5Z" /></svg>
        @break
    @case('sun')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="4" /><path stroke-linecap="round" d="M12 2v2.5M12 19.5V22M4.93 4.93l1.77 1.77M17.3 17.3l1.77 1.77M2 12h2.5M19.5 12H22M4.93 19.07l1.77-1.77M17.3 6.7l1.77-1.77" /></svg>
        @break
    @case('bell')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17H5.5a1 1 0 0 1-.8-1.6L6 13.5V10a6 6 0 1 1 12 0v3.5l1.3 1.9a1 1 0 0 1-.8 1.6H18" /><path stroke-linecap="round" d="M9.5 19a2.5 2.5 0 0 0 5 0" /></svg>
        @break
    @case('filter')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M7 12h10M10 18h4" /></svg>
        @break
    @case('arrow-right')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-5-5 5 5-5 5" /></svg>
        @break
    @case('refresh')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20 11a8 8 0 1 0 2 5.3M20 4v7h-7" /></svg>
        @break
    @case('sparkles')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m12 3 1.8 4.8L18.5 9l-4.7 1.2L12 15l-1.8-4.8L5.5 9l4.7-1.2L12 3ZM19 15l.9 2.1L22 18l-2.1.9L19 21l-.9-2.1L16 18l2.1-.9L19 15ZM5 15l.9 2.1L8 18l-2.1.9L5 21l-.9-2.1L2 18l2.1-.9L5 15Z" /></svg>
        @break
    @case('check-square')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="3" /><path stroke-linecap="round" stroke-linejoin="round" d="m8 12 2.5 2.5L16 9" /></svg>
        @break
    @case('settings')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15.5A3.5 3.5 0 1 0 12 8.5a3.5 3.5 0 0 0 0 7Zm7.4-3.5a7.8 7.8 0 0 0-.1-1l2-1.6-2-3.4-2.5 1a8.9 8.9 0 0 0-1.8-1L14.7 2h-5.4l-.3 3a8.9 8.9 0 0 0-1.8 1l-2.5-1-2 3.4 2 1.6a7.8 7.8 0 0 0 0 2l-2 1.6 2 3.4 2.5-1a8.9 8.9 0 0 0 1.8 1l.3 3h5.4l.3-3a8.9 8.9 0 0 0 1.8-1l2.5 1 2-3.4-2-1.6c.1-.3.1-.7.1-1Z" /></svg>
        @break
    @case('menu')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" /></svg>
        @break
    @case('x')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" d="m6 6 12 12M18 6 6 18" /></svg>
        @break
    @case('chevron-down')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" /></svg>
        @break
    @case('user')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18 20a6 6 0 0 0-12 0" /><circle cx="12" cy="8" r="4" /></svg>
        @break
    @case('users')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16 19a4 4 0 0 0-8 0" /><circle cx="12" cy="11" r="3" /><path stroke-linecap="round" stroke-linejoin="round" d="M20 19a4 4 0 0 0-3-3.87" /><path stroke-linecap="round" stroke-linejoin="round" d="M7 15.13A4 4 0 0 0 4 19" /><path stroke-linecap="round" stroke-linejoin="round" d="M17 7.5a2.5 2.5 0 1 1 0 5" /><path stroke-linecap="round" stroke-linejoin="round" d="M7 7.5a2.5 2.5 0 1 0 0 5" /></svg>
        @break
    @case('shield')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l7 3v5c0 4.2-2.7 8.1-7 10-4.3-1.9-7-5.8-7-10V6l7-3Z" /></svg>
        @break
    @case('database')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><ellipse cx="12" cy="6" rx="7" ry="3" /><path stroke-linecap="round" stroke-linejoin="round" d="M5 6v6c0 1.66 3.13 3 7 3s7-1.34 7-3V6" /><path stroke-linecap="round" stroke-linejoin="round" d="M5 12v6c0 1.66 3.13 3 7 3s7-1.34 7-3v-6" /></svg>
        @break
    @case('layers')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m12 4 8 4-8 4-8-4 8-4Z" /><path stroke-linecap="round" stroke-linejoin="round" d="m4 12 8 4 8-4" /><path stroke-linecap="round" stroke-linejoin="round" d="m4 16 8 4 8-4" /></svg>
        @break
    @case('chart-bar')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20V10" /><path stroke-linecap="round" stroke-linejoin="round" d="M10 20V4" /><path stroke-linecap="round" stroke-linejoin="round" d="M16 20v-7" /><path stroke-linecap="round" stroke-linejoin="round" d="M22 20v-12" /></svg>
        @break
    @case('pie-chart')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v9h9" /><path stroke-linecap="round" stroke-linejoin="round" d="M20.5 12A8.5 8.5 0 1 1 12 3.5" /></svg>
        @break
    @case('activity')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12h4l2.2-5 4.6 10 2.2-5H21" /></svg>
        @break
    @case('image')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" /><circle cx="8.5" cy="10" r="1.5" /><path stroke-linecap="round" stroke-linejoin="round" d="m21 16-5.2-5.2a1 1 0 0 0-1.4 0L8 17" /></svg>
        @break
    @case('logout')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10 17v2a1 1 0 0 0 1 1h7a1 1 0 0 0 1-1V5a1 1 0 0 0-1-1h-7a1 1 0 0 0-1 1v2" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12H4m7-4-4 4 4 4" /></svg>
        @break
    @case('search')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="6.5" /><path stroke-linecap="round" d="m16 16 4.5 4.5" /></svg>
        @break
    @default
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9" /></svg>
@endswitch