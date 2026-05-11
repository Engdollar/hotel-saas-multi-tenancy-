<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="type-page-title" style="color: var(--text-primary);">Template Documentation</h1>
            <p class="type-body mt-1 text-muted">Reference guide for placeholders, supported content, and how template values are rendered.</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        <section class="panel p-5 sm:p-7">
            <p class="section-kicker">How it works</p>
            <h2 class="type-section-title mt-2" style="color: var(--text-primary);">Write templates with placeholders</h2>
            <p class="type-body mt-3 text-muted">You can type normal text, HTML for email body content, and placeholder tokens. When the system generates a PDF, Excel file, or email, it replaces each token with real data from the project or the current action.</p>

            <div class="mt-6 grid gap-4 lg:grid-cols-2">
                <div class="surface-soft rounded-[1.5rem] p-5">
                    <p class="text-sm font-semibold" style="color: var(--text-primary);">Example input</p>
                    <div class="mt-3 rounded-[1.25rem] border px-4 py-3 text-sm" style="border-color: var(--panel-border); color: var(--text-primary); background: color-mix(in srgb, var(--panel-soft) 82%, transparent);">
                        Welcome to @{{ project_title }}
                    </div>
                </div>
                <div class="surface-soft rounded-[1.5rem] p-5">
                    <p class="text-sm font-semibold" style="color: var(--text-primary);">Rendered result</p>
                    <div class="mt-3 rounded-[1.25rem] border px-4 py-3 text-sm" style="border-color: var(--panel-border); color: var(--text-primary); background: color-mix(in srgb, var(--panel-soft) 82%, transparent);">
                        Welcome to {{ $appSettings->get('project_title', config('app.name')) }}
                    </div>
                </div>
            </div>
        </section>

        <section class="panel p-5 sm:p-7">
            <p class="section-kicker">Available variables</p>
            <h2 class="type-section-title mt-2" style="color: var(--text-primary);">Supported placeholders</h2>
            <div class="mt-5 overflow-hidden rounded-[1.5rem] border" style="border-color: var(--panel-border);">
                <table class="min-w-full divide-y" style="divide-color: var(--panel-border);">
                    <thead style="background: color-mix(in srgb, var(--panel-soft) 92%, transparent);">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-[0.18em]" style="color: var(--text-muted);">Placeholder</th>
                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-[0.18em]" style="color: var(--text-muted);">Meaning</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($templateVariables as $variable)
                            <tr class="border-t" style="border-color: var(--panel-border);">
                                <td class="px-4 py-4 align-top text-sm font-semibold" style="color: var(--text-primary);">{{ $variable['token'] }}</td>
                                <td class="px-4 py-4 align-top text-sm text-muted">{{ $variable['description'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel p-5 sm:p-7">
            <p class="section-kicker">Template type notes</p>
            <h2 class="type-section-title mt-2" style="color: var(--text-primary);">What each editor accepts</h2>
            <div class="mt-5 grid gap-4 lg:grid-cols-3">
                <article class="surface-soft rounded-[1.5rem] p-5">
                    <p class="text-sm font-black uppercase tracking-[0.18em] text-muted">PDF headers</p>
                    <p class="mt-3 text-sm text-muted">Use placeholders inside text fields like kicker, title, and subtitle. Color fields should stay as valid color values such as #9d3948.</p>
                </article>
                <article class="surface-soft rounded-[1.5rem] p-5">
                    <p class="text-sm font-black uppercase tracking-[0.18em] text-muted">Excel headers</p>
                    <p class="mt-3 text-sm text-muted">Use placeholders in title and subtitle fields. Color fields control workbook row styling and should remain valid HEX values.</p>
                </article>
                <article class="surface-soft rounded-[1.5rem] p-5">
                    <p class="text-sm font-black uppercase tracking-[0.18em] text-muted">Email templates</p>
                    <p class="mt-3 text-sm text-muted">Subject, greeting, headline, and signature support placeholders. The body field accepts HTML, so you can use tags like &lt;p&gt;, &lt;strong&gt;, and &lt;br&gt;.</p>
                </article>
            </div>
        </section>
    </div>
</x-app-layout>
