<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Documentation | {{ config('app.name') }}</title>
    <x-app-assets />
    <style>
        .docs-prose {
            color: rgba(226, 232, 240, 0.86);
            line-height: 1.8;
        }

        .docs-prose h1,
        .docs-prose h2,
        .docs-prose h3,
        .docs-prose h4 {
            color: #f8fafc;
            font-weight: 600;
            letter-spacing: -0.02em;
            line-height: 1.2;
            margin-top: 1.8rem;
            margin-bottom: 0.9rem;
        }

        .docs-prose h1 {
            font-size: 2rem;
        }

        .docs-prose h2 {
            font-size: 1.4rem;
        }

        .docs-prose h3 {
            font-size: 1.08rem;
        }

        .docs-prose p,
        .docs-prose ul,
        .docs-prose ol,
        .docs-prose pre,
        .docs-prose blockquote {
            margin-top: 0.95rem;
            margin-bottom: 0.95rem;
        }

        .docs-prose ul,
        .docs-prose ol {
            padding-left: 1.35rem;
        }

        .docs-prose li + li {
            margin-top: 0.35rem;
        }

        .docs-prose code {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 0.5rem;
            color: #fde68a;
            font-size: 0.92em;
            padding: 0.12rem 0.38rem;
        }

        .docs-prose pre {
            background: rgba(7, 17, 29, 0.78);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1rem;
            overflow-x: auto;
            padding: 1rem;
        }

        .docs-prose pre code {
            background: transparent;
            border: 0;
            color: #e2e8f0;
            padding: 0;
        }

        .docs-prose blockquote {
            border-left: 3px solid rgba(245, 208, 164, 0.5);
            color: rgba(226, 232, 240, 0.72);
            padding-left: 1rem;
        }

        .docs-prose a {
            color: #fcd34d;
            text-decoration: underline;
            text-underline-offset: 0.22rem;
        }
    </style>
</head>
<body class="min-h-screen" style="background: radial-gradient(circle at top left, rgba(245, 158, 11, 0.18), transparent 30%), radial-gradient(circle at bottom right, rgba(14, 165, 233, 0.14), transparent 24%), linear-gradient(180deg, #09111c 0%, #0f1726 100%); color: #f8fafc;">
    <main class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <section class="rounded-[2rem] border p-6 sm:p-8" style="border-color: rgba(255,255,255,0.08); background: rgba(7, 17, 29, 0.78); backdrop-filter: blur(18px);">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm uppercase tracking-[0.34em]" style="color: rgba(245, 208, 164, 0.86);">Documentation</p>
                    <h1 class="mt-3 text-4xl font-semibold tracking-tight">Project handbook and operational reference.</h1>
                    <p class="mt-3 max-w-3xl text-sm leading-6" style="color: rgba(226, 232, 240, 0.72);">This page now renders the actual project guides, includes operational commands, and adds visual screen previews for the most important routes so the documentation is useful in-browser instead of being just a short summary.</p>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('install.create') }}" class="btn-secondary">Open Installer</a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-primary">Open Workspace</a>
                    @else
                        <a href="{{ route('login') }}" class="btn-primary">Sign In</a>
                    @endauth
                </div>
            </div>
        </section>

        <section class="mt-8 grid gap-8 lg:grid-cols-[0.32fr_1fr]">
            <aside class="rounded-[2rem] border p-5 sm:p-6" style="border-color: rgba(255,255,255,0.08); background: rgba(7, 17, 29, 0.62); backdrop-filter: blur(16px);">
                <p class="text-xs font-semibold uppercase tracking-[0.28em]" style="color: rgba(148, 163, 184, 0.72);">Sections</p>
                <nav class="mt-4 space-y-2">
                    <a href="#screen-tour" class="block rounded-[1rem] border px-4 py-3 text-sm font-semibold transition" style="border-color: rgba(255,255,255,0.06); background: rgba(255,255,255,0.03); color: rgba(241, 245, 249, 0.92);">Screen Tour</a>
                    @foreach ($guides as $guide)
                        <a href="#{{ $guide['id'] }}" class="block rounded-[1rem] border px-4 py-3 text-sm font-semibold transition" style="border-color: rgba(255,255,255,0.06); background: rgba(255,255,255,0.03); color: rgba(241, 245, 249, 0.92);">{{ $guide['title'] }}</a>
                    @endforeach
                </nav>

                <div class="mt-6 rounded-[1.5rem] border p-4" style="border-color: rgba(255,255,255,0.06); background: rgba(255,255,255,0.03);">
                    <p class="text-xs font-semibold uppercase tracking-[0.28em]" style="color: rgba(148, 163, 184, 0.72);">Core Commands</p>
                    <div class="mt-4 space-y-2">
                        @foreach ($commands as $command)
                            <div class="rounded-xl border px-3 py-2 text-sm" style="border-color: rgba(255,255,255,0.06); background: rgba(7, 17, 29, 0.5);">{{ $command }}</div>
                        @endforeach
                    </div>
                </div>
            </aside>

            <div class="space-y-6">
                <article id="screen-tour" class="rounded-[2rem] border p-6 sm:p-7" style="border-color: rgba(255,255,255,0.08); background: rgba(7, 17, 29, 0.7); backdrop-filter: blur(14px);">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.28em]" style="color: rgba(148, 163, 184, 0.72);">Visual guide</p>
                            <h2 class="mt-2 text-2xl font-semibold">Screen tour and previews</h2>
                        </div>
                        <p class="max-w-2xl text-sm leading-6" style="color: rgba(226, 232, 240, 0.72);">The repository did not contain stored screenshot files, so this documentation page includes live visual previews of the key routes. They function as up-to-date screen references without going stale after UI changes.</p>
                    </div>

                    <div class="mt-6 grid gap-5 xl:grid-cols-2">
                        @foreach ($screenTour as $screen)
                            <section class="rounded-[1.5rem] border p-4" style="border-color: rgba(255,255,255,0.06); background: rgba(255,255,255,0.03);">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h3 class="text-lg font-semibold">{{ $screen['title'] }}</h3>
                                        <p class="mt-2 text-sm leading-6" style="color: rgba(226, 232, 240, 0.68);">{{ $screen['caption'] }}</p>
                                    </div>
                                    <span class="rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em]" style="background: rgba(245, 208, 164, 0.12); color: rgba(255, 243, 224, 0.96);">{{ $screen['visibility'] }}</span>
                                </div>
                                <div class="mt-4 overflow-hidden rounded-[1.25rem] border" style="border-color: rgba(255,255,255,0.06); background: rgba(7, 17, 29, 0.45);">
                                    <div class="flex items-center gap-2 border-b px-4 py-3 text-xs" style="border-color: rgba(255,255,255,0.06); color: rgba(148, 163, 184, 0.8);">
                                        <span class="h-2.5 w-2.5 rounded-full" style="background: #ef4444;"></span>
                                        <span class="h-2.5 w-2.5 rounded-full" style="background: #f59e0b;"></span>
                                        <span class="h-2.5 w-2.5 rounded-full" style="background: #10b981;"></span>
                                        <span class="ml-2 truncate">{{ $screen['url'] }}</span>
                                    </div>
                                    <iframe src="{{ $screen['url'] }}" title="{{ $screen['title'] }} preview" loading="lazy" class="h-[340px] w-full bg-white"></iframe>
                                </div>
                                <div class="mt-4 flex items-center justify-between gap-3">
                                    <p class="text-xs uppercase tracking-[0.22em]" style="color: rgba(148, 163, 184, 0.72);">Live preview</p>
                                    <a href="{{ $screen['url'] }}" class="text-sm font-semibold" style="color: rgba(245, 208, 164, 0.9);">Open page</a>
                                </div>
                            </section>
                        @endforeach
                    </div>
                </article>

                @foreach ($guides as $guide)
                    <article id="{{ $guide['id'] }}" class="rounded-[2rem] border p-6 sm:p-7" style="border-color: rgba(255,255,255,0.08); background: rgba(7, 17, 29, 0.7); backdrop-filter: blur(14px);">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.28em]" style="color: rgba(148, 163, 184, 0.72);">{{ str_pad((string) ($loop->iteration), 2, '0', STR_PAD_LEFT) }}</p>
                                <h2 class="mt-2 text-2xl font-semibold">{{ $guide['title'] }}</h2>
                            </div>
                            <div class="max-w-2xl text-sm leading-6" style="color: rgba(226, 232, 240, 0.72);">
                                <p>{{ $guide['summary'] }}</p>
                                <p class="mt-2 text-xs uppercase tracking-[0.22em]" style="color: rgba(148, 163, 184, 0.7);">{{ $guide['reading_time'] }} min read | {{ $guide['path'] }}</p>
                            </div>
                        </div>
                        <div class="docs-prose mt-6 max-w-none rounded-[1.5rem] border px-5 py-5 sm:px-6" style="border-color: rgba(255,255,255,0.06); background: rgba(255,255,255,0.03);">
                            {!! $guide['html'] !!}
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    </main>
</body>
</html>