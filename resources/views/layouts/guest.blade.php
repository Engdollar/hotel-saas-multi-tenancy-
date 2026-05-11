@props([
    'illustration' => 'login',
])

@php
    $themeMode = (string) $appSettings->get('theme_mode', 'dark');
    $themePreset = (string) $appSettings->get('theme_preset', 'cleopatra');
    $authVisuals = [
        'login' => [
            'mode' => (string) $appSettings->get('auth_login_visual_mode', 'default'),
            'image' => \App\Support\AssetPath::storageUrl($appSettings->get('auth_login_visual_image')),
        ],
        'register' => [
            'mode' => (string) $appSettings->get('auth_register_visual_mode', 'default'),
            'image' => \App\Support\AssetPath::storageUrl($appSettings->get('auth_register_visual_image')),
        ],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme-preset="{{ $themePreset }}" @class(['dark' => $themeMode === 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $appSettings->get('project_title', config('app.name', 'Laravel')) }}</title>
        @if ($appSettings->get('favicon'))
            <link rel="icon" type="image/png" href="{{ \App\Support\AssetPath::storageUrl($appSettings->get('favicon')) }}">
        @endif

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument+sans:400,500,600,700,800&display=swap" rel="stylesheet" />

        <script>
            (() => {
                const root = document.documentElement;
                const mode = @json($themeMode);
                const preset = @json($themePreset);
                const previewSignatureKey = 'theme-preview-default-signature';
                const previewSignature = `${mode}:${preset}`;
                const storedSignature = localStorage.getItem(previewSignatureKey);

                if (storedSignature !== previewSignature) {
                    localStorage.removeItem('theme-preview-mode');
                    localStorage.removeItem('theme-preview-preset');
                    localStorage.setItem(previewSignatureKey, previewSignature);
                }

                const resolvedMode = localStorage.getItem('theme-preview-mode') ?? mode;

                localStorage.removeItem('theme-preview-preset');

                root.dataset.themePreset = preset;
                root.classList.toggle('dark', resolvedMode === 'dark' || (resolvedMode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches));
            })();
        </script>

        <!-- Scripts -->
        <x-app-assets />
        @if (! empty($appThemePresetStyles))
            <style>
{!! $appThemePresetStyles !!}
            </style>
        @endif
    </head>
    <body
        class="font-sans antialiased"
        data-theme-mode="{{ $themeMode }}"
        data-theme-preset="{{ $themePreset }}"
        data-allow-preset-preview="false"
        x-data='themeManager({ mode: $el.dataset.themeMode, preset: $el.dataset.themePreset, allowPresetPreview: false })'
    >
        @php
            $projectTitle = $appSettings->get('project_title', config('app.name', 'Laravel'));
        @endphp
        <div class="relative flex min-h-screen items-center justify-center overflow-hidden px-3 py-6 sm:px-4 sm:py-10">
            <div class="absolute inset-0" style="background: var(--app-bg-gradient);"></div>
            <div class="relative grid w-full max-w-6xl overflow-hidden rounded-[1.5rem] border shadow-2xl backdrop-blur xl:grid-cols-[1.15fr_0.85fr]" style="border-color: var(--panel-border); background: color-mix(in srgb, var(--shell-surface) 100%, transparent); box-shadow: 0 34px 80px -42px var(--shadow-color);">
                <div class="hidden p-10 xl:flex xl:flex-col xl:justify-between" style="color: var(--text-primary);">
                    <a href="/" class="inline-flex items-center gap-4">
                        <x-application-logo />
                        <div class="min-w-0">
                            <p class="truncate text-lg font-black tracking-[0.08em]" style="color: var(--text-primary);">{{ $projectTitle }}</p>
                            <p class="mt-1 text-xs font-semibold uppercase tracking-[0.28em]" style="color: var(--accent);">Secure Access Portal</p>
                        </div>
                    </a>

                    <div class="mx-auto flex w-full max-w-xl flex-1 items-center justify-center py-8">
                        <div class="surface-soft relative w-full overflow-hidden px-8 py-10">
                            <div class="absolute -left-10 top-8 h-28 w-28 rounded-full blur-3xl" style="background: color-mix(in srgb, var(--accent) 30%, transparent);"></div>
                            <div class="absolute -right-8 bottom-6 h-32 w-32 rounded-full blur-3xl" style="background: color-mix(in srgb, var(--text-primary) 14%, transparent);"></div>
                            <div class="relative z-10">
                                @if ($illustration === 'register')
                                    @if (($authVisuals['register']['mode'] ?? 'default') === 'custom-image' && ! empty($authVisuals['register']['image']))
                                        <div class="auth-visual-frame mt-6">
                                            <img src="{{ $authVisuals['register']['image'] }}" alt="Registration visual" class="auth-visual-image">
                                            <div class="pointer-events-none absolute inset-x-5 bottom-5 rounded-[1.25rem] border px-4 py-3 backdrop-blur" style="border-color: color-mix(in srgb, var(--accent) 24%, var(--panel-border)); background: color-mix(in srgb, var(--panel-bg) 72%, transparent);">
                                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em]" style="color: var(--accent);">Register visual</p>
                                                <p class="mt-1 text-sm font-semibold" style="color: var(--text-primary);">Custom media from settings</p>
                                            </div>
                                        </div>
                                    @else
                                    <svg viewBox="0 0 520 360" class="mt-6 w-full" role="img" aria-label="Animated registration illustration">
                                        <defs>
                                            <linearGradient id="register-panel-gradient" x1="106" y1="44" x2="408" y2="318" gradientUnits="userSpaceOnUse">
                                                <stop offset="0" stop-color="var(--panel-bg)" />
                                                <stop offset="1" stop-color="var(--panel-soft)" />
                                            </linearGradient>
                                            <linearGradient id="register-accent-gradient" x1="148" y1="134" x2="376" y2="292" gradientUnits="userSpaceOnUse">
                                                <stop offset="0" stop-color="var(--accent)" stop-opacity="0.94" />
                                                <stop offset="1" stop-color="var(--accent-strong)" stop-opacity="0.82" />
                                            </linearGradient>
                                        </defs>

                                        <circle cx="86" cy="92" r="38" fill="var(--accent)" opacity="0.12">
                                            <animate attributeName="r" values="38;46;38" dur="6s" repeatCount="indefinite" />
                                        </circle>
                                        <circle cx="440" cy="72" r="24" fill="var(--text-primary)" opacity="0.08">
                                            <animate attributeName="cx" values="440;418;440" dur="5.6s" repeatCount="indefinite" />
                                        </circle>
                                        <circle cx="430" cy="286" r="34" fill="var(--accent)" opacity="0.11">
                                            <animate attributeName="cy" values="286;304;286" dur="6.2s" repeatCount="indefinite" />
                                        </circle>

                                        <g>
                                            <rect x="98" y="48" width="324" height="254" rx="38" fill="url(#register-panel-gradient)" stroke="var(--panel-border)" stroke-width="2" />
                                            <rect x="126" y="82" width="148" height="16" rx="8" fill="var(--text-primary)" opacity="0.12" />
                                            <rect x="126" y="110" width="210" height="11" rx="5.5" fill="var(--panel-border)" opacity="0.78" />
                                            <rect x="126" y="132" width="184" height="10" rx="5" fill="var(--panel-border)" opacity="0.56" />
                                        </g>

                                        <g>
                                            <rect x="128" y="164" width="164" height="102" rx="26" fill="url(#register-accent-gradient)">
                                                <animateTransform attributeName="transform" type="translate" values="0 0; 0 -6; 0 0" dur="4.7s" repeatCount="indefinite" />
                                            </rect>
                                            <circle cx="210" cy="196" r="20" fill="var(--accent-contrast)" opacity="0.96" />
                                            <path d="M180 244C180 226.327 194.327 212 212 212H208C225.673 212 240 226.327 240 244V248H180V244Z" fill="var(--accent-contrast)" opacity="0.94" />
                                            <rect x="170" y="256" width="82" height="9" rx="4.5" fill="var(--accent-contrast)" opacity="0.56" />
                                        </g>

                                        <g opacity="0.98">
                                            <rect x="146" y="178" width="130" height="56" rx="28" fill="var(--accent-contrast)" opacity="0.2" />
                                            <ellipse cx="192" cy="206" rx="22" ry="14" fill="var(--accent-contrast)" opacity="0.96" />
                                            <ellipse cx="230" cy="206" rx="22" ry="14" fill="var(--accent-contrast)" opacity="0.96" />
                                            <g :transform="`translate(${authEyeOffsetX * 0.75} ${authEyeOffsetY * 0.75})`">
                                                <circle :r="anyPasswordVisible ? 8 : 6.5" cx="192" cy="206" fill="var(--accent-strong)" />
                                                <circle :r="anyPasswordVisible ? 8 : 6.5" cx="230" cy="206" fill="var(--accent-strong)" />
                                                <circle cx="195" cy="203" r="2" fill="var(--accent-contrast)" opacity="0.95" />
                                                <circle cx="233" cy="203" r="2" fill="var(--accent-contrast)" opacity="0.95" />
                                            </g>
                                            <path x-show="authAttention === 'password' || anyPasswordVisible" x-cloak d="M170 186C176 176 184 172 192 172C200 172 208 176 214 186" stroke="var(--accent-contrast)" stroke-width="5" stroke-linecap="round" opacity="0.7" />
                                            <path x-show="authAttention === 'password' || anyPasswordVisible" x-cloak d="M208 186C214 176 222 172 230 172C238 172 246 176 252 186" stroke="var(--accent-contrast)" stroke-width="5" stroke-linecap="round" opacity="0.7" />
                                        </g>

                                        <g x-show="strengthMood === 'weak'" x-cloak opacity="0.85">
                                            <rect x="318" y="174" width="94" height="18" rx="9" fill="#d67f5f" opacity="0.24" />
                                            <rect x="318" y="200" width="64" height="10" rx="5" fill="#d67f5f" opacity="0.36" />
                                            <path d="M164 152C182 144 198 142 214 144" stroke="#d67f5f" stroke-width="6" stroke-linecap="round" opacity="0.42" />
                                        </g>

                                        <g x-show="strengthMood === 'good'" x-cloak opacity="0.9">
                                            <circle cx="358" cy="104" r="18" fill="var(--accent)" opacity="0.16">
                                                <animate attributeName="r" values="18;24;18" dur="2.8s" repeatCount="indefinite" />
                                            </circle>
                                            <path d="M160 156C180 142 198 138 214 140" stroke="var(--accent)" stroke-width="6" stroke-linecap="round" opacity="0.42" />
                                            <path d="M208 140C220 136 232 136 246 142" stroke="var(--accent)" stroke-width="6" stroke-linecap="round" opacity="0.28" />
                                        </g>

                                        <g x-show="strengthMood === 'strong'" x-cloak opacity="0.96">
                                            <circle cx="360" cy="102" r="26" fill="var(--accent)" opacity="0.18">
                                                <animate attributeName="r" values="26;34;26" dur="2.1s" repeatCount="indefinite" />
                                            </circle>
                                            <circle cx="360" cy="102" r="10" fill="var(--accent)" opacity="0.78" />
                                            <path d="M326 116l14 14 28-34" stroke="var(--accent)" stroke-width="8" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M164 160C182 134 210 126 242 136" stroke="var(--accent)" stroke-width="7" stroke-linecap="round" opacity="0.48" />
                                            <path d="M178 288C208 274 238 270 268 272" stroke="var(--accent)" stroke-width="5" stroke-linecap="round" opacity="0.28" />
                                        </g>

                                        <g>
                                            <rect x="306" y="150" width="92" height="116" rx="24" fill="var(--panel-bg)" stroke="var(--panel-border)" stroke-width="2">
                                                <animateTransform attributeName="transform" type="translate" values="0 0; 0 7; 0 0" dur="5.4s" repeatCount="indefinite" />
                                            </rect>
                                            <rect x="328" y="176" width="48" height="48" rx="16" fill="var(--accent)" opacity="0.16" />
                                            <path d="M352 184V216" stroke="var(--accent)" stroke-width="10" stroke-linecap="round" />
                                            <path d="M336 200H368" stroke="var(--accent)" stroke-width="10" stroke-linecap="round" />
                                            <rect x="324" y="234" width="56" height="9" rx="4.5" fill="var(--text-primary)" opacity="0.12" />
                                            <rect x="332" y="249" width="40" height="8" rx="4" fill="var(--text-primary)" opacity="0.08" />
                                        </g>

                                        <g opacity="0.9">
                                            <path d="M122 312C164 286 210 274 260 274C320 274 370 286 404 312" stroke="var(--accent)" stroke-width="3" stroke-linecap="round" stroke-dasharray="8 10">
                                                <animate attributeName="stroke-dashoffset" values="0;36" dur="3.4s" repeatCount="indefinite" />
                                            </path>
                                        </g>

                                        <g x-show="preset === 'midnight' || preset === 'lagoon'" x-cloak opacity="0.95">
                                            <rect x="340" y="92" width="98" height="40" rx="20" fill="var(--text-primary)" opacity="0.1" />
                                            <circle cx="366" cy="112" r="7" fill="var(--accent)" />
                                            <rect x="380" y="108" width="34" height="8" rx="4" fill="var(--accent)" opacity="0.6" />
                                        </g>
                                    </svg>
                                    @endif
                                @else
                                    @if (($authVisuals['login']['mode'] ?? 'default') === 'custom-image' && ! empty($authVisuals['login']['image']))
                                        <div class="auth-visual-frame mt-6">
                                            <img src="{{ $authVisuals['login']['image'] }}" alt="Login visual" class="auth-visual-image">
                                            <div class="pointer-events-none absolute inset-x-5 bottom-5 rounded-[1.25rem] border px-4 py-3 backdrop-blur" style="border-color: color-mix(in srgb, var(--accent) 24%, var(--panel-border)); background: color-mix(in srgb, var(--panel-bg) 72%, transparent);">
                                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em]" style="color: var(--accent);">Login visual</p>
                                                <p class="mt-1 text-sm font-semibold" style="color: var(--text-primary);">Custom media from settings</p>
                                            </div>
                                        </div>
                                    @else
                                    <svg viewBox="0 0 520 360" class="mt-6 w-full" role="img" aria-label="Animated secure login illustration">
                                        <defs>
                                            <linearGradient id="auth-card-gradient" x1="88" y1="54" x2="420" y2="320" gradientUnits="userSpaceOnUse">
                                                <stop offset="0" stop-color="var(--panel-bg)" />
                                                <stop offset="1" stop-color="var(--panel-soft)" />
                                            </linearGradient>
                                            <linearGradient id="auth-accent-gradient" x1="166" y1="114" x2="350" y2="278" gradientUnits="userSpaceOnUse">
                                                <stop offset="0" stop-color="var(--accent)" stop-opacity="0.95" />
                                                <stop offset="1" stop-color="var(--accent-strong)" stop-opacity="0.88" />
                                            </linearGradient>
                                        </defs>

                                        <circle cx="82" cy="82" r="36" fill="var(--accent)" opacity="0.16">
                                            <animate attributeName="cy" values="82;66;82" dur="6s" repeatCount="indefinite" />
                                        </circle>
                                        <circle cx="444" cy="84" r="20" fill="var(--text-primary)" opacity="0.11">
                                            <animate attributeName="cy" values="84;98;84" dur="5.5s" repeatCount="indefinite" />
                                        </circle>
                                        <circle cx="452" cy="292" r="32" fill="var(--accent)" opacity="0.14">
                                            <animate attributeName="cx" values="452;432;452" dur="7s" repeatCount="indefinite" />
                                        </circle>

                                        <g>
                                            <rect x="104" y="52" width="312" height="246" rx="34" fill="url(#auth-card-gradient)" stroke="var(--panel-border)" stroke-width="2" />
                                            <rect x="132" y="84" width="256" height="12" rx="6" fill="var(--panel-border)" opacity="0.78" />
                                            <rect x="132" y="112" width="164" height="16" rx="8" fill="var(--text-primary)" opacity="0.16" />
                                            <rect x="132" y="144" width="200" height="14" rx="7" fill="var(--text-primary)" opacity="0.1" />
                                        </g>

                                        <g>
                                            <rect x="162" y="134" width="196" height="124" rx="28" fill="url(#auth-accent-gradient)" opacity="0.98">
                                                <animateTransform attributeName="transform" type="translate" values="0 0; 0 -5; 0 0" dur="4.8s" repeatCount="indefinite" />
                                            </rect>
                                            <ellipse cx="224" cy="186" rx="34" ry="22" fill="var(--accent-contrast)" opacity="0.98" />
                                            <ellipse cx="296" cy="186" rx="34" ry="22" fill="var(--accent-contrast)" opacity="0.98" />
                                            <g :transform="`translate(${authEyeOffsetX} ${authEyeOffsetY}) scale(${authEyeScale})`" style="transform-origin: 260px 186px; transform-box: fill-box;">
                                                <circle :r="anyPasswordVisible ? 13 : 10" cx="224" cy="186" fill="var(--accent-strong)" />
                                                <circle :r="anyPasswordVisible ? 13 : 10" cx="296" cy="186" fill="var(--accent-strong)" />
                                                <circle cx="228" cy="181" r="3" fill="var(--accent-contrast)" opacity="0.95" />
                                                <circle cx="300" cy="181" r="3" fill="var(--accent-contrast)" opacity="0.95" />
                                            </g>
                                            <path x-show="authAttention === 'password' || anyPasswordVisible" x-cloak d="M192 154C202 138 214 132 226 132C238 132 249 138 258 154" stroke="var(--accent-contrast)" stroke-width="6" stroke-linecap="round" opacity="0.76" />
                                            <path x-show="authAttention === 'password' || anyPasswordVisible" x-cloak d="M262 154C272 138 284 132 296 132C308 132 319 138 328 154" stroke="var(--accent-contrast)" stroke-width="6" stroke-linecap="round" opacity="0.76" />
                                            <rect x="194" y="234" width="132" height="10" rx="5" fill="var(--accent-contrast)" opacity="0.42" />
                                            <rect x="210" y="252" width="100" height="8" rx="4" fill="var(--accent-contrast)" opacity="0.28" />
                                            <g x-show="authAttention === 'password'" x-cloak opacity="0.72">
                                                <rect x="176" y="144" width="168" height="6" rx="3" fill="var(--accent-contrast)">
                                                    <animate attributeName="y" values="144;250;144" dur="2.2s" repeatCount="indefinite" />
                                                </rect>
                                            </g>

                                            <g x-show="strengthMood === 'weak'" x-cloak opacity="0.88">
                                                <path d="M206 150C214 138 224 134 234 134" stroke="#d67f5f" stroke-width="6" stroke-linecap="round" opacity="0.56" />
                                                <path d="M286 150C294 138 304 134 314 134" stroke="#d67f5f" stroke-width="6" stroke-linecap="round" opacity="0.56" />
                                            </g>

                                            <g x-show="strengthMood === 'good'" x-cloak opacity="0.9">
                                                <circle cx="178" cy="128" r="14" fill="var(--accent)" opacity="0.14">
                                                    <animate attributeName="cy" values="128;118;128" dur="2.6s" repeatCount="indefinite" />
                                                </circle>
                                                <circle cx="346" cy="124" r="14" fill="var(--accent)" opacity="0.14">
                                                    <animate attributeName="cy" values="124;114;124" dur="2.4s" repeatCount="indefinite" />
                                                </circle>
                                            </g>

                                            <g x-show="strengthMood === 'strong'" x-cloak opacity="0.98">
                                                <circle cx="260" cy="146" r="54" fill="var(--accent)" opacity="0.12">
                                                    <animate attributeName="r" values="54;66;54" dur="2.2s" repeatCount="indefinite" />
                                                </circle>
                                                <path d="M214 154C224 140 236 134 250 134C264 134 276 140 286 154" stroke="var(--accent-contrast)" stroke-width="7" stroke-linecap="round" opacity="0.82" />
                                                <path d="M286 154C296 140 308 134 322 134" stroke="var(--accent-contrast)" stroke-width="7" stroke-linecap="round" opacity="0.82" />
                                            </g>

                                            <g x-show="anyPasswordVisible" x-cloak opacity="0.96">
                                                <circle cx="328" cy="150" r="16" fill="var(--accent-contrast)" opacity="0.24">
                                                    <animate attributeName="r" values="16;22;16" dur="1.8s" repeatCount="indefinite" />
                                                </circle>
                                                <path d="M322 151l5 5 10-13" stroke="var(--accent-contrast)" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" />
                                            </g>
                                        </g>

                                        <g opacity="0.96">
                                            <rect x="330" y="104" width="104" height="74" rx="22" fill="var(--panel-bg)" stroke="var(--panel-border)" stroke-width="2">
                                                <animateTransform attributeName="transform" type="translate" values="0 0; 0 -8; 0 0" dur="5.8s" repeatCount="indefinite" />
                                            </rect>
                                            <path d="M382 120C370.954 120 362 128.954 362 140V146H356C351.582 146 348 149.582 348 154V166C348 170.418 351.582 174 356 174H408C412.418 174 416 170.418 416 166V154C416 149.582 412.418 146 408 146H402V140C402 128.954 393.046 120 382 120ZM370 140C370 133.373 375.373 128 382 128C388.627 128 394 133.373 394 140V146H370V140Z" fill="var(--accent)" />
                                        </g>

                                        <g opacity="0.94">
                                            <rect x="92" y="214" width="118" height="86" rx="24" fill="var(--panel-bg)" stroke="var(--panel-border)" stroke-width="2">
                                                <animateTransform attributeName="transform" type="translate" values="0 0; 0 8; 0 0" dur="5.2s" repeatCount="indefinite" />
                                            </rect>
                                            <path d="M130 256L148 274L178 238" stroke="var(--accent)" stroke-width="12" stroke-linecap="round" stroke-linejoin="round" />
                                        </g>

                                        <g opacity="0.68">
                                            <path d="M130 330C164 296 222 284 264 284C314 284 368 296 400 330" stroke="var(--panel-border)" stroke-width="2.5" stroke-linecap="round" stroke-dasharray="7 10">
                                                <animate attributeName="stroke-dashoffset" values="0;34" dur="3s" repeatCount="indefinite" />
                                            </path>
                                        </g>

                                        <g x-show="preset === 'midnight'" x-cloak opacity="0.95">
                                            <rect x="116" y="68" width="76" height="26" rx="13" fill="var(--accent)" opacity="0.22" />
                                            <rect x="354" y="248" width="84" height="30" rx="15" fill="var(--accent)" opacity="0.22" />
                                            <circle cx="390" cy="144" r="48" fill="var(--accent)" opacity="0.08">
                                                <animate attributeName="r" values="48;58;48" dur="5s" repeatCount="indefinite" />
                                            </circle>
                                        </g>

                                        <g x-show="preset === 'sakura'" x-cloak opacity="0.94">
                                            <rect x="340" y="86" width="108" height="96" rx="28" fill="var(--accent)" opacity="0.14" />
                                            <path d="M366 134L382 150L418 112" stroke="var(--accent)" stroke-width="12" stroke-linecap="round" stroke-linejoin="round" />
                                            <rect x="100" y="226" width="98" height="62" rx="20" fill="var(--text-primary)" opacity="0.06" />
                                        </g>

                                        <g x-show="preset === 'cleopatra' || preset === 'citrus'" x-cloak opacity="0.9">
                                            <rect x="330" y="104" width="104" height="74" rx="22" fill="var(--accent)" opacity="0.12" />
                                            <circle cx="128" cy="256" r="12" fill="var(--accent)" opacity="0.24" />
                                            <circle cx="166" cy="256" r="12" fill="var(--accent)" opacity="0.14" />
                                            <circle cx="204" cy="256" r="12" fill="var(--accent)" opacity="0.1" />
                                        </g>

                                        <g x-show="preset === 'lagoon' || preset === 'ember'" x-cloak opacity="0.94">
                                            <path d="M100 120C132 96 170 86 208 86" stroke="var(--accent)" stroke-width="6" stroke-linecap="round" opacity="0.34" />
                                            <path d="M322 282C356 262 390 258 426 266" stroke="var(--accent)" stroke-width="6" stroke-linecap="round" opacity="0.34" />
                                            <rect x="350" y="94" width="70" height="18" rx="9" fill="var(--accent)" opacity="0.2" />
                                        </g>
                                    </svg>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-5 py-7 sm:px-8 sm:py-8 xl:px-12 xl:py-10" style="background: color-mix(in srgb, var(--panel-bg) 100%, transparent);">
                    <div class="mx-auto w-full max-w-md">
                        <div class="xl:hidden">
                            <a href="/" class="inline-flex items-center gap-3">
                                <x-application-logo />
                                <div class="min-w-0">
                                    <p class="truncate text-base font-black tracking-[0.06em]" style="color: var(--text-primary);">{{ $projectTitle }}</p>
                                    <p class="mt-1 text-[11px] font-semibold uppercase tracking-[0.24em]" style="color: var(--accent);">Secure Access Portal</p>
                                </div>
                            </a>
                        </div>
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
