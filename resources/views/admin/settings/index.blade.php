<x-app-layout>
    @php
        $settings = collect($settings);
        $selectedPdfTemplateSlug = old('pdf_header_template', $settings->get('pdf_header_template', $selectedPdfHeaderTemplate['slug']));
        $selectedExcelTemplateSlug = old('excel_header_template', $settings->get('excel_header_template', $selectedExcelHeaderTemplate['slug']));
        $selectedEmailTemplateSlug = old('email_template', $settings->get('email_template', $selectedEmailTemplate['slug']));
        $projectLogoUrl = \App\Support\AssetPath::storageUrl($settings->get('logo'));
        $projectInitials = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', (string) $settings->get('project_title', config('app.name'))), 0, 2) ?: 'PR');

        $pdfTemplateFormState = [
            'kicker' => old('pdf_header_kicker', data_get($pdfHeaderTemplates, $selectedPdfTemplateSlug.'.content.kicker', '')),
            'title' => old('pdf_header_title', data_get($pdfHeaderTemplates, $selectedPdfTemplateSlug.'.content.title', '')),
            'subtitle' => old('pdf_header_subtitle', data_get($pdfHeaderTemplates, $selectedPdfTemplateSlug.'.content.subtitle', '')),
            'accent_start' => old('pdf_header_accent_start', data_get($pdfHeaderTemplates, $selectedPdfTemplateSlug.'.content.accent_start', '')),
            'accent_end' => old('pdf_header_accent_end', data_get($pdfHeaderTemplates, $selectedPdfTemplateSlug.'.content.accent_end', '')),
            'surface' => old('pdf_header_surface', data_get($pdfHeaderTemplates, $selectedPdfTemplateSlug.'.content.surface', '')),
            'border' => old('pdf_header_border', data_get($pdfHeaderTemplates, $selectedPdfTemplateSlug.'.content.border', '')),
            'text_primary' => old('pdf_header_text_primary', data_get($pdfHeaderTemplates, $selectedPdfTemplateSlug.'.content.text_primary', '')),
            'text_muted' => old('pdf_header_text_muted', data_get($pdfHeaderTemplates, $selectedPdfTemplateSlug.'.content.text_muted', '')),
            'heading_background' => old('pdf_header_heading_background', data_get($pdfHeaderTemplates, $selectedPdfTemplateSlug.'.content.heading_background', '')),
            'heading_text' => old('pdf_header_heading_text', data_get($pdfHeaderTemplates, $selectedPdfTemplateSlug.'.content.heading_text', '')),
        ];

        $excelTemplateFormState = [
            'title' => old('excel_header_title', data_get($excelHeaderTemplates, $selectedExcelTemplateSlug.'.content.title', '')),
            'subtitle' => old('excel_header_subtitle', data_get($excelHeaderTemplates, $selectedExcelTemplateSlug.'.content.subtitle', '')),
            'accent' => old('excel_header_accent', data_get($excelHeaderTemplates, $selectedExcelTemplateSlug.'.content.accent', '')),
            'title_text' => old('excel_header_title_text', data_get($excelHeaderTemplates, $selectedExcelTemplateSlug.'.content.title_text', '')),
            'meta_background' => old('excel_header_meta_background', data_get($excelHeaderTemplates, $selectedExcelTemplateSlug.'.content.meta_background', '')),
            'meta_text' => old('excel_header_meta_text', data_get($excelHeaderTemplates, $selectedExcelTemplateSlug.'.content.meta_text', '')),
            'heading_background' => old('excel_header_heading_background', data_get($excelHeaderTemplates, $selectedExcelTemplateSlug.'.content.heading_background', '')),
            'heading_text' => old('excel_header_heading_text', data_get($excelHeaderTemplates, $selectedExcelTemplateSlug.'.content.heading_text', '')),
            'body_border' => old('excel_header_body_border', data_get($excelHeaderTemplates, $selectedExcelTemplateSlug.'.content.body_border', '')),
        ];

        $emailTemplateFormState = [
            'subject' => old('email_subject', data_get($emailTemplates, $selectedEmailTemplateSlug.'.content.subject', '')),
            'headline' => old('email_headline', data_get($emailTemplates, $selectedEmailTemplateSlug.'.content.headline', '')),
            'greeting' => old('email_greeting', data_get($emailTemplates, $selectedEmailTemplateSlug.'.content.greeting', '')),
            'body_html' => old('email_body_html', data_get($emailTemplates, $selectedEmailTemplateSlug.'.content.body_html', '')),
            'button_label' => old('email_button_label', data_get($emailTemplates, $selectedEmailTemplateSlug.'.content.button_label', '')),
            'signature' => old('email_signature', data_get($emailTemplates, $selectedEmailTemplateSlug.'.content.signature', '')),
            'accent' => old('email_accent', data_get($emailTemplates, $selectedEmailTemplateSlug.'.content.accent', '')),
            'surface' => old('email_surface', data_get($emailTemplates, $selectedEmailTemplateSlug.'.content.surface', '')),
        ];
    @endphp

    <x-slot name="header">
        <div>
            <h1 class="type-page-title" style="color: var(--text-primary);">Settings</h1>
            <p class="type-body mt-1 text-muted">Manage company branding, themes, auth visuals, and tenant-facing workspace settings.</p>
        </div>
    </x-slot>

    @role('Super Admin')
        <x-slot name="subSidebar">
            <div class="settings-subnav-shell">
                <div class="settings-subnav-header">
                    <p class="section-kicker">Settings map</p>
                    <h2 class="settings-subnav-title">Control center</h2>
                    <p class="settings-subnav-copy">Super Admin only</p>
                </div>

                <div class="settings-subnav-group">
                    <p class="settings-subnav-kicker">Workspace</p>
                    <a href="#settings-widgets" class="settings-subnav-link"><span class="settings-subnav-label"><span class="settings-subnav-index">01</span>Widgets</span></a>
                    <a href="#dashboard-studio" class="settings-subnav-link"><span class="settings-subnav-label"><span class="settings-subnav-index">02</span>Dashboard studio</span></a>
                    <a href="#branding" class="settings-subnav-link"><span class="settings-subnav-label"><span class="settings-subnav-index">03</span>Branding</span></a>
                    <a href="#themes" class="settings-subnav-link"><span class="settings-subnav-label"><span class="settings-subnav-index">04</span>Themes</span></a>
                    <a href="#document-templates" class="settings-subnav-link"><span class="settings-subnav-label"><span class="settings-subnav-index">05</span>Export templates</span></a>
                    <a href="#email-templates" class="settings-subnav-link"><span class="settings-subnav-label"><span class="settings-subnav-index">06</span>Email templates</span></a>
                </div>

                <div class="settings-subnav-group">
                    <p class="settings-subnav-kicker">Presets</p>
                    <a href="#preset-studio" class="settings-subnav-link"><span class="settings-subnav-label"><span class="settings-subnav-index">07</span>Preset studio</span></a>
                    <a href="#saved-presets" class="settings-subnav-link"><span class="settings-subnav-label"><span class="settings-subnav-index">08</span>Saved presets</span></a>
                </div>

                <div class="settings-subnav-group">
                    <p class="settings-subnav-kicker">Access</p>
                    <a href="#generator" class="settings-subnav-link"><span class="settings-subnav-label"><span class="settings-subnav-index">09</span>RBAC generator</span></a>
                    <a href="#permission-groups" class="settings-subnav-link"><span class="settings-subnav-label"><span class="settings-subnav-index">10</span>Permission groups</span></a>
                </div>
            </div>
        </x-slot>
    @endrole

    <div class="space-y-5">
        @role('Super Admin')
            <div id="settings-widgets" class="settings-anchor-section" x-data='dashboardWidgets({ widgets: @json($widgetState), layout: @json($widgetLayout), available: @json(array_keys($dashboardWidgetOptions)), dragEnabled: @json($dragEnabled), canManageDrag: @json($canDragWidgets), endpoint: "{{ route('admin.dashboard.widgets') }}", csrfToken: "{{ csrf_token() }}", controlsOpen: false, bootDelay: 0 })'>
            <div class="panel p-5 sm:p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="section-kicker">Dashboard widgets</p>
                        <h2 class="type-section-title mt-2" style="color: var(--text-primary);">Manage widget visibility</h2>
                        <p class="type-body mt-2 text-muted">Turn widgets on or off here. Dragging is limited to Super Admin and its status stays saved.</p>
                    </div>
                    <div class="flex flex-col gap-3 xl:items-end">
                        <button type="button" class="widget-chip" :class="dragAllowed ? 'is-active' : ''" @click="toggleDrag()">
                            <span x-text="dragAllowed ? 'Disable drag mode' : 'Enable drag mode'"></span>
                        </button>
                        <div class="widget-toggle-grid widget-toggle-grid-balanced xl:max-w-[42rem] xl:justify-end">
                        @foreach ($dashboardWidgetOptions as $widgetKey => $widget)
                            <button type="button" class="widget-chip" :class="widgets['{{ $widgetKey }}'] ? 'is-active' : ''" @click="toggleWidget('{{ $widgetKey }}')">{{ $widget['label'] }}</button>
                        @endforeach
                        </div>
                    </div>
                </div>
            </div>
            </div>
        @endrole

    <div class="space-y-6">
        <form method="POST" action="{{ route('admin.settings.dashboard-studio.update') }}" enctype="multipart/form-data" class="panel p-5 sm:p-6 space-y-6 settings-anchor-section" id="dashboard-studio" data-async-form="true" data-loading-message="Saving dashboard studio...">
            @csrf
            @method('PUT')

            <div x-data='dashboardStudioEditor({ stats: @json($dashboardStudio['stats']), charts: @json($dashboardStudio['charts']), icons: @json($dashboardIconOptions), statSources: @json($dashboardStatSources), chartSources: @json($dashboardChartSources), chartTypes: @json($dashboardChartTypeOptions), authVisuals: @json($dashboardStudio['auth_visuals']) })' class="space-y-6">
                <input type="hidden" name="dashboard_stats" x-ref="statsInput">
                <input type="hidden" name="dashboard_charts" x-ref="chartsInput">

                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="section-kicker">Dashboard studio</p>
                        <h2 class="type-section-title mt-2" style="color: var(--text-primary);">Stats, charts, auth media</h2>
                        <p class="type-body mt-2 text-muted">Set what appears, which source feeds it, and which auth visual is used.</p>
                    </div>
                    <a href="{{ route('admin.settings.dashboard-documentation') }}" target="_blank" rel="noopener noreferrer" class="btn-secondary w-full sm:w-auto">Open documentation</a>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="section-kicker">Stats cards</p>
                            <p class="type-body mt-1 text-muted">Choose how many cards you want and what each one reads.</p>
                        </div>
                        <button type="button" class="btn-secondary w-full sm:w-auto" @click="addStat()">Add stat</button>
                    </div>

                    <div class="space-y-4">
                        <template x-for="(stat, index) in stats" :key="stat.id">
                            <div class="theme-card space-y-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <p class="type-card-title" style="color: var(--text-primary);" x-text="stat.label || 'Stat card'"></p>
                                    <div class="flex gap-2">
                                        <button type="button" class="widget-chip" @click="moveStat(index, -1)">Up</button>
                                        <button type="button" class="widget-chip" @click="moveStat(index, 1)">Down</button>
                                        <button type="button" class="widget-chip" @click="removeStat(index)">Remove</button>
                                    </div>
                                </div>
                                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                    <div>
                                        <label class="type-body font-semibold" style="color: var(--text-primary);">Label</label>
                                        <input type="text" class="form-input" x-model="stat.label">
                                    </div>
                                    <div>
                                        <label class="type-body font-semibold" style="color: var(--text-primary);">Icon</label>
                                        <select class="form-input" x-model="stat.icon">
                                            <template x-for="(label, value) in icons" :key="value">
                                                <option :value="value" x-text="label"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="type-body font-semibold" style="color: var(--text-primary);">Source</label>
                                        <select class="form-input" x-model="stat.source">
                                            <template x-for="(meta, value) in statSources" :key="value">
                                                <option :value="value" x-text="`${meta.label} (${meta.table})`"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="type-body font-semibold" style="color: var(--text-primary);">Description</label>
                                        <input type="text" class="form-input" x-model="stat.description">
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="section-kicker">Charts</p>
                            <p class="type-body mt-1 text-muted">Configure line, pie, and bar charts from safe data sources.</p>
                        </div>
                        <button type="button" class="btn-secondary w-full sm:w-auto" @click="addChart()">Add chart</button>
                    </div>

                    <div class="space-y-4">
                        <template x-for="(chart, index) in charts" :key="chart.id">
                            <div class="theme-card space-y-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <p class="type-card-title" style="color: var(--text-primary);" x-text="chart.title || 'Chart widget'"></p>
                                    <div class="flex gap-2">
                                        <button type="button" class="widget-chip" @click="moveChart(index, -1)">Up</button>
                                        <button type="button" class="widget-chip" @click="moveChart(index, 1)">Down</button>
                                        <button type="button" class="widget-chip" @click="removeChart(index)">Remove</button>
                                    </div>
                                </div>
                                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                                    <div>
                                        <label class="type-body font-semibold" style="color: var(--text-primary);">Title</label>
                                        <input type="text" class="form-input" x-model="chart.title">
                                    </div>
                                    <div>
                                        <label class="type-body font-semibold" style="color: var(--text-primary);">Source</label>
                                        <select class="form-input" x-model="chart.source" @change="normalizeChart(index)">
                                            <template x-for="(meta, value) in chartSources" :key="value">
                                                <option :value="value" x-text="`${meta.label} (${meta.table})`"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="type-body font-semibold" style="color: var(--text-primary);">Type</label>
                                        <select class="form-input" x-model="chart.type">
                                            <template x-for="type in chartTypeChoices(chart.source)" :key="type">
                                                <option :value="type" x-text="chartTypes[type] ?? type"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="type-body font-semibold" style="color: var(--text-primary);">Audience</label>
                                        <select class="form-input" x-model="chart.audience">
                                            <option value="all">All users</option>
                                            <option value="super-admin">Super Admin</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="type-body font-semibold" style="color: var(--text-primary);">Description</label>
                                        <input type="text" class="form-input" x-model="chart.description">
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <p class="section-kicker">Auth visuals</p>
                        <p class="type-body mt-1 text-muted">Use the built-in animation or upload a custom moving image for login and register.</p>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-2">
                        <div class="theme-card space-y-4">
                            <div class="flex items-center justify-between gap-3">
                                <p class="type-card-title" style="color: var(--text-primary);">Login visual</p>
                                <span class="selection-chip is-selected">Login</span>
                            </div>
                            <div>
                                <label class="type-body font-semibold" style="color: var(--text-primary);">Mode</label>
                                <select name="auth_login_visual_mode" class="form-input" x-model="authVisuals.login_mode">
                                    <option value="default">Default animation</option>
                                    <option value="custom-image">Custom image</option>
                                </select>
                            </div>
                            <div>
                                <label class="type-body font-semibold" style="color: var(--text-primary);">Image</label>
                                <input name="auth_login_visual_image" type="file" class="form-input" accept="image/*" @change="previewImage($event, 'login')">
                            </div>
                            <template x-if="authVisuals.login_mode === 'custom-image' && loginPreview">
                                <div class="auth-visual-frame">
                                    <img :src="loginPreview" alt="Login preview" class="auth-visual-image">
                                </div>
                            </template>
                        </div>

                        <div class="theme-card space-y-4">
                            <div class="flex items-center justify-between gap-3">
                                <p class="type-card-title" style="color: var(--text-primary);">Register visual</p>
                                <span class="selection-chip is-selected">Register</span>
                            </div>
                            <div>
                                <label class="type-body font-semibold" style="color: var(--text-primary);">Mode</label>
                                <select name="auth_register_visual_mode" class="form-input" x-model="authVisuals.register_mode">
                                    <option value="default">Default animation</option>
                                    <option value="custom-image">Custom image</option>
                                </select>
                            </div>
                            <div>
                                <label class="type-body font-semibold" style="color: var(--text-primary);">Image</label>
                                <input name="auth_register_visual_image" type="file" class="form-input" accept="image/*" @change="previewImage($event, 'register')">
                            </div>
                            <template x-if="authVisuals.register_mode === 'custom-image' && registerPreview">
                                <div class="auth-visual-frame">
                                    <img :src="registerPreview" alt="Register preview" class="auth-visual-image">
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="btn-primary w-full sm:w-auto">Save dashboard studio</button>
                </div>
            </div>
        </form>

        <form
            method="POST"
            action="{{ route('admin.settings.update') }}"
            enctype="multipart/form-data"
            class="panel p-5 sm:p-7"
            data-async-form="true"
            data-loading-message="Saving branding and theme..."
            data-selected-preset="{{ old('theme_preset', $settings->get('theme_preset', 'cleopatra')) }}"
            data-selected-mode="{{ old('theme_mode', $settings->get('theme_mode', 'dark')) }}"
            x-data='themeSettings($el.dataset.selectedPreset, $el.dataset.selectedMode)'
        >
            @csrf
            @method('PUT')
            <input type="hidden" name="theme_preset" x-model="selectedPreset">
            <input type="hidden" name="theme_mode" x-model="selectedMode">

            <div id="branding" class="settings-anchor-section">
                <p class="section-kicker">Branding</p>
                <h2 class="type-section-title mt-2" style="color: var(--text-primary);">Project identity</h2>
            </div>

            <div class="mt-8 space-y-6">
                <div>
                    <label for="project_title" class="type-body font-semibold" style="color: var(--text-primary);">Project title</label>
                    <input id="project_title" name="project_title" type="text" class="form-input" value="{{ old('project_title', $settings->get('project_title', config('app.name'))) }}" required>
                    <x-input-error :messages="$errors->get('project_title')" class="mt-2" />
                </div>

                <div class="grid gap-4 md:grid-cols-2 lg:gap-5">
                    <div>
                        <label for="logo" class="type-body font-semibold" style="color: var(--text-primary);">Logo</label>
                        <input id="logo" name="logo" type="file" class="form-input" accept="image/*">
                        @if ($settings->get('logo'))
                            <img src="{{ \App\Support\AssetPath::storageUrl($settings->get('logo')) }}" alt="Logo preview" class="mt-3 h-16 rounded-2xl p-2" style="background: color-mix(in srgb, var(--panel-soft) 100%, transparent);">
                        @endif
                        <x-input-error :messages="$errors->get('logo')" class="mt-2" />
                    </div>

                    <div>
                        <label for="favicon" class="type-body font-semibold" style="color: var(--text-primary);">Favicon</label>
                        <input id="favicon" name="favicon" type="file" class="form-input" accept="image/*">
                        @if ($settings->get('favicon'))
                            <img src="{{ \App\Support\AssetPath::storageUrl($settings->get('favicon')) }}" alt="Favicon preview" class="mt-3 h-16 w-16 rounded-2xl p-2" style="background: color-mix(in srgb, var(--panel-soft) 100%, transparent);">
                        @endif
                        <x-input-error :messages="$errors->get('favicon')" class="mt-2" />
                    </div>
                </div>

                @if (auth()->user()?->isSuperAdmin())
                    <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_auto] md:items-end lg:gap-5">
                        <div>
                            <label for="tenancy_base_domain" class="type-body font-semibold" style="color: var(--text-primary);">Tenancy base domain</label>
                            <input id="tenancy_base_domain" name="tenancy_base_domain" type="text" class="form-input" value="{{ old('tenancy_base_domain', $tenancyBaseDomain) }}" placeholder="eelo-university.test">
                            <p class="mt-2 text-sm text-muted">Use the real shared host here. Companies can then be saved as just a subdomain from the Company Control Center.</p>
                            <x-input-error :messages="$errors->get('tenancy_base_domain')" class="mt-2" />
                        </div>
                        <div class="rounded-3xl border px-4 py-3 text-sm" style="border-color: var(--panel-border); background: color-mix(in srgb, var(--panel-soft) 82%, transparent); color: var(--text-primary);">
                            <p class="font-semibold">Session scope</p>
                            <p class="mt-1 text-muted">{{ $tenancyBaseDomain ? '.'.$tenancyBaseDomain : 'No shared session domain configured yet.' }}</p>
                        </div>
                    </div>
                @endif

                <div id="themes" class="settings-anchor-section">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="section-kicker">Theme presets</p>
                            <h3 class="type-section-title mt-2" style="color: var(--text-primary);">Choose a default compound theme</h3>
                        </div>
                    </div>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        @foreach ($themePresets as $key => $preset)
                            <button type="button" @click='selectPreset(@json($key))' class="theme-card theme-card-button" :class='selectedPreset === @json($key) ? "is-selected" : ""'>
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="type-card-title" style="color: var(--text-primary);">{{ $preset['name'] }}</p>
                                        <p class="type-body mt-2 text-muted">{{ $preset['description'] }}</p>
                                    </div>
                                    <span class="selection-chip {{ old('theme_preset', $settings->get('theme_preset', 'cleopatra')) === $key ? 'is-selected' : '' }}" :class='selectedPreset === @json($key) ? "is-selected" : ""' x-text='selectedPreset === @json($key) ? "Selected" : "Preset"'>{{ old('theme_preset', $settings->get('theme_preset', 'cleopatra')) === $key ? 'Selected' : 'Preset' }}</span>
                                </div>
                                <div class="mt-4 flex gap-2">
                                    @foreach ($preset['swatches'] as $swatch)
                                        <span class="h-9 w-9 rounded-2xl border" style="background: {{ $swatch }}; border-color: var(--panel-border);"></span>
                                    @endforeach
                                </div>
                            </button>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('theme_preset')" class="mt-2" />
                </div>

                <div>
                    <p class="section-kicker">Theme mode</p>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($themeModes as $value => $label)
                            <button type="button" @click='selectMode(@json($value))' class="theme-card theme-card-button" :class='selectedMode === @json($value) ? "is-selected" : ""'>
                                <p class="type-card-title" style="color: var(--text-primary);">{{ $label }}</p>
                                <p class="type-body mt-2 text-muted">
                                    @if ($value === 'light')
                                        Use the muted daytime palette.
                                    @elseif ($value === 'dark')
                                        Use the low-glare dark palette.
                                    @else
                                        Follow the device preference.
                                    @endif
                                </p>
                            </button>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('theme_mode')" class="mt-2" />
                </div>
            </div>

            <div class="mt-8 flex items-center justify-end">
                <button type="submit" class="btn-primary w-full sm:w-auto">Save settings</button>
            </div>
        </form>

        <div class="space-y-6">
            <div class="panel p-5 sm:p-6 settings-anchor-section" id="document-templates">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="section-kicker">Document headers</p>
                        <h2 class="type-section-title mt-2" style="color: var(--text-primary);">PDF and Excel templates</h2>
                        <p class="type-body mt-1 text-muted">Choose a built-in preset, edit it, and save the selected export header to the database.</p>
                    </div>
                    <span class="selection-chip is-selected">Editable presets</span>
                </div>

                <div class="mt-5 space-y-6">
                    <form method="POST" action="{{ route('admin.settings.document-templates.update') }}" class="panel p-5 sm:p-6 space-y-5" data-async-form="true" data-loading-message="Saving PDF template...">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="template_scope" value="pdf">

                        <div x-data='Object.assign(templateEditor(@json($pdfHeaderTemplates), @json($selectedPdfTemplateSlug), @json($pdfTemplateFormState)), { activeTab: "settings" })' class="space-y-5">
                        <input type="hidden" name="pdf_header_template" x-model="selectedSlug">
                        <div class="flex flex-col gap-3 border-b pb-5 sm:flex-row sm:items-center sm:justify-between" style="border-color: var(--panel-border);">
                            <div>
                                <p class="section-kicker">PDF header preset</p>
                                <p class="type-body mt-2 text-muted">Use the settings tab to define the template, then switch to preview to see the final export header.</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" class="selection-chip" :class="activeTab === 'settings' ? 'is-selected' : ''" @click="activeTab = 'settings'">Template settings</button>
                                <button type="button" class="selection-chip" :class="activeTab === 'preview' ? 'is-selected' : ''" @click="activeTab = 'preview'">Preview</button>
                            </div>
                        </div>

                        <div x-show="activeTab === 'settings'" class="space-y-5">
                            <div class="flex flex-col gap-3 rounded-[1.5rem] border px-4 py-4 sm:flex-row sm:items-center sm:justify-between" style="border-color: var(--panel-border); background: color-mix(in srgb, var(--panel-soft) 78%, transparent);">
                                <div>
                                    <p class="text-sm font-semibold" style="color: var(--text-primary);">Need help with placeholders?</p>
                                    <p class="mt-1 text-sm text-muted">Open the documentation to understand values like @{{ project_title }} and the other supported variables.</p>
                                </div>
                                <a href="{{ route('admin.settings.template-documentation') }}" target="_blank" rel="noopener noreferrer" class="btn-secondary w-full sm:w-auto">Open documentation</a>
                            </div>

                            <div>
                                <div class="mt-3 grid gap-3 sm:grid-cols-3">
                                    @foreach ($pdfHeaderTemplates as $slug => $template)
                                        <button type="button" class="theme-card theme-card-button text-left" :class='selectedSlug === @json($slug) ? "is-selected" : ""' @click='selectTemplate(@json($slug))'>
                                            <div class="flex items-start justify-between gap-3">
                                                <div>
                                                    <p class="type-card-title" style="color: var(--text-primary);">{{ $template['name'] }}</p>
                                                    <p class="type-body mt-2 text-muted">{{ $template['description'] }}</p>
                                                </div>
                                                <span class="selection-chip" :class='selectedSlug === @json($slug) ? "is-selected" : ""' x-text='selectedSlug === @json($slug) ? "Selected" : "Preset"'></span>
                                            </div>
                                            <div class="mt-4 flex gap-2">
                                                @foreach ($template['swatches'] as $swatch)
                                                    <span class="h-8 w-8 rounded-2xl border" style="background: {{ $swatch }}; border-color: var(--panel-border);"></span>
                                                @endforeach
                                            </div>
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="type-body font-semibold" style="color: var(--text-primary);">Kicker</label>
                                    <input name="pdf_header_kicker" type="text" class="form-input" x-model="fields.kicker">
                                </div>
                                <div>
                                    <label class="type-body font-semibold" style="color: var(--text-primary);">Title</label>
                                    <input name="pdf_header_title" type="text" class="form-input" x-model="fields.title">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="type-body font-semibold" style="color: var(--text-primary);">Subtitle</label>
                                    <input name="pdf_header_subtitle" type="text" class="form-input" x-model="fields.subtitle">
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                <div><label class="type-body font-semibold" style="color: var(--text-primary);">Accent start</label><input name="pdf_header_accent_start" type="text" class="form-input" x-model="fields.accent_start"></div>
                                <div><label class="type-body font-semibold" style="color: var(--text-primary);">Accent end</label><input name="pdf_header_accent_end" type="text" class="form-input" x-model="fields.accent_end"></div>
                                <div><label class="type-body font-semibold" style="color: var(--text-primary);">Surface</label><input name="pdf_header_surface" type="text" class="form-input" x-model="fields.surface"></div>
                                <div><label class="type-body font-semibold" style="color: var(--text-primary);">Border</label><input name="pdf_header_border" type="text" class="form-input" x-model="fields.border"></div>
                                <div><label class="type-body font-semibold" style="color: var(--text-primary);">Text primary</label><input name="pdf_header_text_primary" type="text" class="form-input" x-model="fields.text_primary"></div>
                                <div><label class="type-body font-semibold" style="color: var(--text-primary);">Text muted</label><input name="pdf_header_text_muted" type="text" class="form-input" x-model="fields.text_muted"></div>
                                <div><label class="type-body font-semibold" style="color: var(--text-primary);">Heading background</label><input name="pdf_header_heading_background" type="text" class="form-input" x-model="fields.heading_background"></div>
                                <div><label class="type-body font-semibold" style="color: var(--text-primary);">Heading text</label><input name="pdf_header_heading_text" type="text" class="form-input" x-model="fields.heading_text"></div>
                            </div>
                        </div>

                        <div x-show="activeTab === 'preview'" class="space-y-4">
                            <div class="rounded-[1.75rem] border p-6" :style="`background:${field('surface', '#f8fafc')}; border-color:${field('border', '#d7dce5')}`">
                                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="flex items-start gap-4">
                                        @if ($projectLogoUrl)
                                            <img src="{{ $projectLogoUrl }}" alt="{{ $settings->get('project_title', config('app.name')) }}" class="h-16 w-16 rounded-[1.25rem] border object-contain p-2" style="border-color: var(--panel-border); background: #ffffff;">
                                        @else
                                            <div class="flex h-16 w-16 items-center justify-center rounded-[1.25rem] text-lg font-black text-white" :style="`background:linear-gradient(135deg, ${field('accent_start', '#9d3948')} 0%, ${field('accent_end', '#c95b69')} 100%)`">{{ $projectInitials }}</div>
                                        @endif
                                        <div class="space-y-3">
                                            <p class="text-xs font-black uppercase tracking-[0.22em]" :style="`color:${field('text_muted', '#617084')}`" x-text="field('kicker', 'Project title')"></p>
                                            <div>
                                                <h3 class="text-2xl font-black" :style="`color:${field('text_primary', '#18212f')}`" x-text="field('title', 'Export title')"></h3>
                                                <p class="mt-2 text-sm" :style="`color:${field('text_muted', '#617084')}`" x-text="field('subtitle', 'Export subtitle')"></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="h-20 w-full rounded-[1.5rem] lg:max-w-[16rem]" :style="`background:linear-gradient(135deg, ${field('accent_start', '#9d3948')} 0%, ${field('accent_end', '#c95b69')} 100%)`"></div>
                                </div>
                                <div class="mt-5 rounded-[1.25rem] border px-4 py-3" :style="`background:${field('heading_background', '#eef2f7')}; border-color:${field('border', '#d7dce5')}`">
                                    <p class="text-sm font-semibold" :style="`color:${field('heading_text', '#455468')}`">Table heading sample</p>
                                </div>
                            </div>
                        </div>

                        <x-input-error :messages="$errors->get('pdf_header_template')" class="mt-2" />
                            <div class="flex justify-end">
                                <button type="submit" class="btn-primary w-full sm:w-auto">Save PDF template</button>
                            </div>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('admin.settings.document-templates.update') }}" class="panel p-5 sm:p-6 space-y-5" data-async-form="true" data-loading-message="Saving Excel template...">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="template_scope" value="excel">

                        <div x-data='Object.assign(templateEditor(@json($excelHeaderTemplates), @json($selectedExcelTemplateSlug), @json($excelTemplateFormState)), { activeTab: "settings" })' class="space-y-5">
                        <input type="hidden" name="excel_header_template" x-model="selectedSlug">
                        <div class="flex flex-col gap-3 border-b pb-5 sm:flex-row sm:items-center sm:justify-between" style="border-color: var(--panel-border);">
                            <div>
                                <p class="section-kicker">Excel header preset</p>
                                <p class="type-body mt-2 text-muted">Keep the workbook colors and title rows in the settings tab, then verify the spreadsheet header in preview.</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" class="selection-chip" :class="activeTab === 'settings' ? 'is-selected' : ''" @click="activeTab = 'settings'">Template settings</button>
                                <button type="button" class="selection-chip" :class="activeTab === 'preview' ? 'is-selected' : ''" @click="activeTab = 'preview'">Preview</button>
                            </div>
                        </div>

                        <div x-show="activeTab === 'settings'" class="space-y-5">
                            <div class="flex flex-col gap-3 rounded-[1.5rem] border px-4 py-4 sm:flex-row sm:items-center sm:justify-between" style="border-color: var(--panel-border); background: color-mix(in srgb, var(--panel-soft) 78%, transparent);">
                                <div>
                                    <p class="text-sm font-semibold" style="color: var(--text-primary);">Variable documentation</p>
                                    <p class="mt-1 text-sm text-muted">Open the documentation if the team needs examples for placeholders like @{{ export_title }}.</p>
                                </div>
                                <a href="{{ route('admin.settings.template-documentation') }}" target="_blank" rel="noopener noreferrer" class="btn-secondary w-full sm:w-auto">Open documentation</a>
                            </div>

                            <div>
                                <div class="mt-3 grid gap-3 sm:grid-cols-3">
                                    @foreach ($excelHeaderTemplates as $slug => $template)
                                        <button type="button" class="theme-card theme-card-button text-left" :class='selectedSlug === @json($slug) ? "is-selected" : ""' @click='selectTemplate(@json($slug))'>
                                            <div class="flex items-start justify-between gap-3">
                                                <div>
                                                    <p class="type-card-title" style="color: var(--text-primary);">{{ $template['name'] }}</p>
                                                    <p class="type-body mt-2 text-muted">{{ $template['description'] }}</p>
                                                </div>
                                                <span class="selection-chip" :class='selectedSlug === @json($slug) ? "is-selected" : ""' x-text='selectedSlug === @json($slug) ? "Selected" : "Preset"'></span>
                                            </div>
                                            <div class="mt-4 flex gap-2">
                                                @foreach ($template['swatches'] as $swatch)
                                                    <span class="h-8 w-8 rounded-2xl border" style="background: {{ $swatch }}; border-color: var(--panel-border);"></span>
                                                @endforeach
                                            </div>
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="type-body font-semibold" style="color: var(--text-primary);">Title</label>
                                    <input name="excel_header_title" type="text" class="form-input" x-model="fields.title">
                                </div>
                                <div>
                                    <label class="type-body font-semibold" style="color: var(--text-primary);">Subtitle</label>
                                    <input name="excel_header_subtitle" type="text" class="form-input" x-model="fields.subtitle">
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                <div><label class="type-body font-semibold" style="color: var(--text-primary);">Accent</label><input name="excel_header_accent" type="text" class="form-input" x-model="fields.accent"></div>
                                <div><label class="type-body font-semibold" style="color: var(--text-primary);">Title text</label><input name="excel_header_title_text" type="text" class="form-input" x-model="fields.title_text"></div>
                                <div><label class="type-body font-semibold" style="color: var(--text-primary);">Meta background</label><input name="excel_header_meta_background" type="text" class="form-input" x-model="fields.meta_background"></div>
                                <div><label class="type-body font-semibold" style="color: var(--text-primary);">Meta text</label><input name="excel_header_meta_text" type="text" class="form-input" x-model="fields.meta_text"></div>
                                <div><label class="type-body font-semibold" style="color: var(--text-primary);">Heading background</label><input name="excel_header_heading_background" type="text" class="form-input" x-model="fields.heading_background"></div>
                                <div><label class="type-body font-semibold" style="color: var(--text-primary);">Heading text</label><input name="excel_header_heading_text" type="text" class="form-input" x-model="fields.heading_text"></div>
                                <div><label class="type-body font-semibold" style="color: var(--text-primary);">Body border</label><input name="excel_header_body_border" type="text" class="form-input" x-model="fields.body_border"></div>
                            </div>
                        </div>

                        <div x-show="activeTab === 'preview'" class="space-y-4">
                            <div class="overflow-hidden rounded-[1.75rem] border" :style="`border-color:${field('body_border', '#d7dce5')}`">
                                <div class="px-5 py-4" :style="`background:${field('accent', '#9d3948')}`">
                                    <div class="flex items-center gap-3">
                                        @if ($projectLogoUrl)
                                            <img src="{{ $projectLogoUrl }}" alt="{{ $settings->get('project_title', config('app.name')) }}" class="h-12 w-12 rounded-2xl bg-white/95 object-contain p-2">
                                        @else
                                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/15 text-sm font-black text-white">{{ $projectInitials }}</div>
                                        @endif
                                        <div>
                                            <h3 class="text-lg font-black" :style="`color:${field('title_text', '#ffffff')}`" x-text="field('title', 'Export title')"></h3>
                                            <p class="mt-1 text-sm" :style="`color:${field('title_text', '#ffffff')}`" x-text="field('subtitle', 'Export subtitle')"></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="px-5 py-3 text-sm" :style="`background:${field('meta_background', '#f7eef0')}; color:${field('meta_text', '#6d4850')}`">
                                    Generated at sample time
                                </div>
                                <div class="px-5 py-3 text-sm font-semibold" :style="`background:${field('heading_background', '#eef2f7')}; color:${field('heading_text', '#455468')}`">
                                    Column heading sample
                                </div>
                                <div class="grid gap-px p-5 sm:grid-cols-3" :style="`background:${field('body_border', '#d7dce5')}`">
                                    <div class="rounded-xl px-4 py-3 text-sm" style="background: var(--panel-bg); color: var(--text-primary);">Cell A1</div>
                                    <div class="rounded-xl px-4 py-3 text-sm" style="background: var(--panel-bg); color: var(--text-primary);">Cell B1</div>
                                    <div class="rounded-xl px-4 py-3 text-sm" style="background: var(--panel-bg); color: var(--text-primary);">Cell C1</div>
                                </div>
                            </div>
                        </div>

                        <x-input-error :messages="$errors->get('excel_header_template')" class="mt-2" />
                            <div class="flex justify-end">
                                <button type="submit" class="btn-primary w-full sm:w-auto">Save Excel template</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="panel p-5 sm:p-6 settings-anchor-section" id="email-templates">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="section-kicker">Email presets</p>
                        <h2 class="type-section-title mt-2" style="color: var(--text-primary);">Prebuilt email templates</h2>
                        <p class="type-body mt-1 text-muted">Pick a prebuilt mail preset, customize the copy, and keep the selected version in the database.</p>
                    </div>
                    <span class="selection-chip">HTML ready</span>
                </div>

                <form method="POST" action="{{ route('admin.settings.email-templates.update') }}" class="mt-5 space-y-6" x-data='Object.assign(templateEditor(@json($emailTemplates), @json($selectedEmailTemplateSlug), @json($emailTemplateFormState)), { activeTab: "settings" })' data-async-form="true" data-loading-message="Saving email template...">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="email_template" x-model="selectedSlug">

                    <div class="flex flex-col gap-3 border-b pb-5 sm:flex-row sm:items-center sm:justify-between" style="border-color: var(--panel-border);">
                        <div>
                            <p class="text-sm font-semibold" style="color: var(--text-primary);">Template workspace</p>
                            <p class="mt-1 text-sm text-muted">Edit the copy in one tab and check the result in preview before saving.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" class="selection-chip" :class="activeTab === 'settings' ? 'is-selected' : ''" @click="activeTab = 'settings'">Template settings</button>
                            <button type="button" class="selection-chip" :class="activeTab === 'preview' ? 'is-selected' : ''" @click="activeTab = 'preview'">Preview</button>
                        </div>
                    </div>

                    <div x-show="activeTab === 'settings'" class="space-y-5">
                        <div class="flex flex-col gap-3 rounded-[1.5rem] border px-4 py-4 sm:flex-row sm:items-center sm:justify-between" style="border-color: var(--panel-border); background: color-mix(in srgb, var(--panel-soft) 78%, transparent);">
                            <div>
                                <p class="text-sm font-semibold" style="color: var(--text-primary);">Email template documentation</p>
                                <p class="mt-1 text-sm text-muted">Open the documentation to understand placeholders like @{{ recipient_name }} and how HTML is rendered.</p>
                            </div>
                            <a href="{{ route('admin.settings.template-documentation') }}" target="_blank" rel="noopener noreferrer" class="btn-secondary w-full sm:w-auto">Open documentation</a>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            @foreach ($emailTemplates as $slug => $template)
                                <button type="button" class="theme-card theme-card-button text-left" :class='selectedSlug === @json($slug) ? "is-selected" : ""' @click='selectTemplate(@json($slug))'>
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="type-card-title" style="color: var(--text-primary);">{{ $template['name'] }}</p>
                                            <p class="type-body mt-2 text-muted">{{ $template['description'] }}</p>
                                        </div>
                                        <span class="selection-chip" :class='selectedSlug === @json($slug) ? "is-selected" : ""' x-text='selectedSlug === @json($slug) ? "Selected" : "Preset"'></span>
                                    </div>
                                    <div class="mt-4 flex gap-2">
                                        @foreach ($template['swatches'] as $swatch)
                                            <span class="h-8 w-8 rounded-2xl border" style="background: {{ $swatch }}; border-color: var(--panel-border);"></span>
                                        @endforeach
                                    </div>
                                </button>
                            @endforeach
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="type-body font-semibold" style="color: var(--text-primary);">Subject</label>
                                <input name="email_subject" type="text" class="form-input" x-model="fields.subject">
                            </div>
                            <div>
                                <label class="type-body font-semibold" style="color: var(--text-primary);">Headline</label>
                                <input name="email_headline" type="text" class="form-input" x-model="fields.headline">
                            </div>
                            <div>
                                <label class="type-body font-semibold" style="color: var(--text-primary);">Greeting</label>
                                <input name="email_greeting" type="text" class="form-input" x-model="fields.greeting">
                            </div>
                            <div>
                                <label class="type-body font-semibold" style="color: var(--text-primary);">Button label</label>
                                <input name="email_button_label" type="text" class="form-input" x-model="fields.button_label">
                            </div>
                            <div class="md:col-span-2">
                                <label class="type-body font-semibold" style="color: var(--text-primary);">Body HTML</label>
                                <textarea name="email_body_html" rows="7" class="form-input" x-init="$nextTick(() => initRichText($el, 'body_html'))"></textarea>
                                <p class="mt-2 text-xs text-muted">Use the editor to format paragraphs, lists, links, and emphasis for the email body.</p>
                            </div>
                            <div class="md:col-span-2">
                                <label class="type-body font-semibold" style="color: var(--text-primary);">Signature</label>
                                <textarea name="email_signature" rows="3" class="form-input" x-model="fields.signature"></textarea>
                            </div>
                            <div>
                                <label class="type-body font-semibold" style="color: var(--text-primary);">Accent</label>
                                <input name="email_accent" type="text" class="form-input" x-model="fields.accent">
                            </div>
                            <div>
                                <label class="type-body font-semibold" style="color: var(--text-primary);">Surface</label>
                                <input name="email_surface" type="text" class="form-input" x-model="fields.surface">
                            </div>
                        </div>
                    </div>

                    <div x-show="activeTab === 'preview'" class="surface-soft rounded-[1.75rem] p-5">
                        <p class="section-kicker">Preview</p>
                        <div class="mt-4 rounded-[1.5rem] border p-5" :style="`background:${field('surface', '#ffffff')}; border-color:${field('accent', '#b54c5a')}22`">
                            <div class="flex items-center gap-3">
                                @if ($projectLogoUrl)
                                    <img src="{{ $projectLogoUrl }}" alt="{{ $settings->get('project_title', config('app.name')) }}" class="h-12 w-12 rounded-2xl border object-contain p-2" style="border-color: var(--panel-border); background: #ffffff;">
                                @else
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl text-sm font-black text-white" :style="`background:${field('accent', '#b54c5a')}`">{{ $projectInitials }}</div>
                                @endif
                                <div class="min-w-0">
                                    <p class="text-xs font-black uppercase tracking-[0.2em]" :style="`color:${field('accent', '#b54c5a')}`" x-text="field('subject')"></p>
                                    <p class="mt-1 text-xs text-muted">{{ $settings->get('project_title', config('app.name')) }}</p>
                                </div>
                            </div>
                            <h3 class="mt-3 text-xl font-black" style="color: var(--text-primary);" x-text="field('headline')"></h3>
                            <p class="mt-3 text-sm text-muted" x-text="field('greeting')"></p>
                            <div class="prose prose-sm mt-4 max-w-none" x-html="field('body_html')"></div>
                            <button type="button" class="btn-primary mt-5" x-text="field('button_label')"></button>
                            <div class="mt-4 text-sm text-muted" x-html="field('signature')"></div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="btn-primary w-full sm:w-auto">Save email template</button>
                    </div>
                </form>
            </div>

            <div class="panel p-5 sm:p-6" id="preset-studio">
                <p class="section-kicker">Preset studio</p>
                <div class="mt-3 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h2 class="type-section-title" style="color: var(--text-primary);">Create presets</h2>
                        <p class="type-body mt-1 text-muted">Generate, preview, save.</p>
                    </div>
                   
                </div>

                <form method="POST" action="{{ route('admin.settings.theme-presets.preview') }}" class="mt-5 space-y-4">
                    @csrf
                    <div>
                        <label for="theme_keywords" class="type-body font-semibold" style="color: var(--text-primary);">Keywords</label>
                        <textarea id="theme_keywords" name="keywords" rows="3" class="form-input" placeholder="red, luxury, velvet">{{ old('keywords') }}</textarea>
                        <p class="mt-2 text-xs text-muted">Mix keywords or use packs below.</p>
                        <x-input-error :messages="$errors->get('keywords')" class="mt-2" />
                    </div>

                    <div>
                        <p class="type-body font-semibold" style="color: var(--text-primary);">Curated keyword packs</p>
                        <div class="preset-pack-grid mt-3">
                            @foreach ($keywordPacks as $packKey => $pack)
                                <label class="preset-pack-card">
                                    <input type="checkbox" name="packs[]" value="{{ $packKey }}" class="sr-only peer" {{ in_array($packKey, old('packs', []), true) ? 'checked' : '' }}>
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="type-body font-semibold" style="color: var(--text-primary);">{{ $pack['label'] }}</p>
                                        <span class="selection-chip peer-checked:is-selected">Pack</span>
                                    </div>
                                    {{-- <p class="mt-2 text-xs uppercase tracking-[0.2em] text-muted">{{ implode(' • ', $pack['keywords']) }}</p> --}}
                                </label>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('packs')" class="mt-2" />
                        <x-input-error :messages="$errors->get('packs.*')" class="mt-2" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_12rem] sm:items-end">
                        <div>
                            <label for="theme_count" class="type-body font-semibold" style="color: var(--text-primary);">Candidate count</label>
                            <select id="theme_count" name="count" class="form-input">
                                @foreach ([12, 24, 50, 100] as $countOption)
                                    <option value="{{ $countOption }}" {{ (int) old('count', 50) === $countOption ? 'selected' : '' }}>{{ $countOption }} presets</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('count')" class="mt-2" />
                        </div>
                        <button type="submit" class="btn-primary w-full">Preview candidates</button>
                    </div>
                </form>

                @if (! empty($generatedThemePresets))
                    <div class="mt-8 border-t pt-6" style="border-color: var(--panel-border);">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="section-kicker">Preview results</p>
                                <h3 class="type-section-title mt-2" style="color: var(--text-primary);">Pick what to keep</h3>
                            </div>
                            <span class="selection-chip">{{ count($generatedThemePresets) }} results</span>
                        </div>

                        <form method="POST" action="{{ route('admin.settings.theme-presets.store') }}" class="mt-5 space-y-4">
                            @csrf
                            <input type="hidden" name="generated_presets" value="{{ base64_encode(json_encode($generatedThemePresets)) }}">

                            <div class="preset-preview-grid max-h-[42rem] overflow-y-auto pr-1">
                                @foreach ($generatedThemePresets as $candidate)
                                    <label class="theme-card preset-preview-card">
                                        <input type="checkbox" name="selected_presets[]" value="{{ $candidate['slug'] }}" class="sr-only peer" {{ $loop->first ? 'checked' : '' }}>
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="preset-check-indicator" aria-hidden="true"></span>
                                                    <p class="type-card-title" style="color: var(--text-primary);">{{ $candidate['name'] }}</p>
                                                </div>
                                                <div class="mt-2 flex flex-wrap gap-2">
                                                    @foreach (array_slice($candidate['keywords'], 0, 3) as $keyword)
                                                        <span class="selection-chip">{{ $keyword }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <span class="selection-chip preset-select-chip">Select</span>
                                        </div>
                                        <div class="mt-3 flex gap-2">
                                            @foreach ($candidate['swatches'] as $swatch)
                                                <span class="h-9 w-9 rounded-2xl border" style="background: {{ $swatch }}; border-color: var(--panel-border);"></span>
                                            @endforeach
                                        </div>
                                    </label>
                                @endforeach
                            </div>

                            <label class="inline-flex items-center gap-3 text-sm text-muted">
                                <input type="checkbox" name="replace_existing" value="1" class="rounded border" style="border-color: var(--panel-border); background: var(--field-bg); color: var(--accent);">
                                <span>Replace matching slugs</span>
                            </label>

                            <x-input-error :messages="$errors->get('selected_presets')" class="mt-2" />

                            <div class="flex justify-end">
                                <button type="submit" class="btn-primary w-full sm:w-auto">Save selected presets</button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>

            <div class="panel p-5 sm:p-6 settings-anchor-section" id="saved-presets">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="section-kicker">Custom library</p>
                        <h2 class="type-section-title mt-2" style="color: var(--text-primary);">Saved presets</h2>
                        <p class="type-body mt-1 text-muted">Duplicate or delete.</p>
                    </div>
                    
                </div>

                @if ($customThemePresets->isEmpty())
                    <div class="surface-soft mt-5 p-5">
                        <p class="type-body text-muted">No custom presets are saved yet. Generate a preview above and save the ones you want to keep.</p>
                    </div>
                @else
                    <div class="mt-5 space-y-4">
                        @foreach ($customThemePresets as $preset)
                            <div class="theme-card">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="type-card-title" style="color: var(--text-primary);">{{ $preset->name }}</p>
                                            <span class="selection-chip">{{ $preset->slug }}</span>
                                            <span class="selection-chip {{ $preset->is_generated ? 'is-selected' : '' }}">{{ $preset->is_generated ? 'Generated' : 'Manual' }}</span>
                                        </div>
                                        @if (! empty($preset->keywords))
                                            <div class="mt-3 flex flex-wrap gap-2">
                                                @foreach (array_slice($preset->keywords, 0, 4) as $keyword)
                                                    <span class="selection-chip">{{ $keyword }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex flex-col items-stretch gap-3 lg:min-w-[14rem]">
                                        <div class="flex gap-2">
                                            @foreach ($preset->swatches as $swatch)
                                                <span class="h-10 w-10 rounded-2xl border" style="background: {{ $swatch }}; border-color: var(--panel-border);"></span>
                                            @endforeach
                                        </div>

                                        <div class="flex flex-wrap gap-2">
                                            <form method="POST" action="{{ route('admin.settings.theme-presets.duplicate', $preset) }}">
                                                @csrf
                                                <button type="submit" class="btn-secondary w-full">Duplicate</button>
                                            </form>

                                            <form method="POST" action="{{ route('admin.settings.theme-presets.destroy', $preset) }}" data-confirm-delete="true" data-confirm-title="Delete {{ addslashes($preset->name) }}?" data-confirm-text="This custom theme preset will be removed from the library." data-loading-message="Deleting theme preset...">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-danger w-full">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="panel generator-card p-5 sm:p-6 settings-anchor-section" id="generator">
                <div class="generator-card-orb generator-card-orb-primary"></div>
                <div class="generator-card-orb generator-card-orb-secondary"></div>

                <div class="generator-card-shell">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="space-y-3">
                            <span class="generator-card-badge">
                                <x-icon name="sparkles" class="h-4 w-4" />
                                Generator
                            </span>

                            <div>
                                <h2 class="type-section-title generator-card-title">Rebuild access control</h2>
                                <p class="type-body mt-2 text-muted">Refresh permissions, roles, and the default admin account in one run.</p>
                            </div>

                            <div class="generator-card-meta">
                                <span class="selection-chip is-selected">Roles</span>
                                <span class="selection-chip is-selected">Permissions</span>
                                <span class="selection-chip">Admin seed</span>
                            </div>
                        </div>

                        <div class="generator-card-icon-wrap" aria-hidden="true">
                            <span class="generator-card-icon generator-card-icon-main"><x-icon name="settings" class="h-5 w-5" /></span>
                            <span class="generator-card-icon generator-card-icon-float"><x-icon name="check-square" class="h-4 w-4" /></span>
                        </div>
                    </div>

                    <div class="generator-card-action-row">
                        <div>
                            <p class="generator-card-caption">Safe to rerun</p>
                            <p class="text-sm text-muted">Use when permissions or admin setup changed.</p>
                        </div>

                        <form method="POST" action="{{ route('admin.settings.generate') }}" class="w-full sm:w-auto">
                            @csrf
                            <button type="submit" class="btn-primary generator-card-button w-full sm:w-auto">
                                <x-icon name="sparkles" class="h-4 w-4" />
                                Generate now
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="panel p-5 sm:p-6 settings-anchor-section" id="permission-groups" x-data="{ open: false }">
              
                <button type="button" class="accordion-trigger" @click="open = !open" :aria-expanded="open.toString()">
                    <div>
                      <p class="section-kicker">Permission groups</p>
                    </div>
                    <span class="accordion-indicator" :class="open ? 'is-open' : ''">
                        <x-icon name="chevron-down" class="h-4 w-4 transition" ::class="open ? 'rotate-180' : ''" />
                    </span>
                </button>
                <div class="mt-4 space-y-4">
                    @foreach ($permissionGroups as $module => $permissions)
                        <div x-show="open" class="surface-soft p-4">
                            <button type="button" >
                                <div>
                                    <h3 class="type-card-title" style="color: var(--text-primary);">{{ $module }}</h3>
                                    <p class="type-meta mt-1 text-muted">{{ $permissions->count() }} permissions</p>
                                </div>
                                
                            </button>
                            <div  x-cloak x-transition class="mt-4 flex flex-wrap gap-2">
                                @foreach ($permissions as $permission)
                                    <span class="selection-chip">{{ $permission->name }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    </div>
</x-app-layout>