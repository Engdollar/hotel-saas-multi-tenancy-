<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Install {{ config('app.name') }}</title>
    <x-app-assets />
</head>
@php
    $requirementFailures = collect($requirementsSummary['requirements'])->where('passes', false)->count();
    $requirementsByCategory = collect($requirementsSummary['requirements'])->groupBy('category');
    $initialStep = 1;

    if ($errors->hasAny(['project_title', 'app_url', 'install_fresh'])) {
        $initialStep = 2;
    } elseif ($errors->hasAny(['db_host', 'db_port', 'db_database', 'db_username', 'db_password'])) {
        $initialStep = 3;
    } elseif ($errors->hasAny(['tenancy_base_domain', 'default_company_name'])) {
        $initialStep = 4;
    } elseif ($errors->hasAny(['admin_name', 'admin_email', 'admin_password', 'requirements'])) {
        $initialStep = 5;
    }
@endphp
<body class="min-h-screen" style="background: radial-gradient(circle at top left, rgba(245, 158, 11, 0.18), transparent 26%), radial-gradient(circle at bottom right, rgba(14, 165, 233, 0.12), transparent 24%), linear-gradient(180deg, #08111d 0%, #0e1726 100%); color: #f8fafc;">
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <section class="grid gap-6 lg:grid-cols-[0.33fr_1fr]">
            <aside class="rounded-[2rem] border p-5 sm:p-6" style="border-color: rgba(255,255,255,0.08); background: rgba(7, 17, 29, 0.72); backdrop-filter: blur(16px);">
                <p class="text-xs font-semibold uppercase tracking-[0.32em]" style="color: rgba(245, 208, 164, 0.86);">Install</p>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight">Platform setup wizard</h1>
                <p class="mt-3 text-sm leading-6" style="color: rgba(226, 232, 240, 0.68);">Move through five short steps and install only after the server and database checks pass.</p>

                <div class="mt-6 space-y-3">
                    <a href="{{ route('documentation.index') }}" class="btn-secondary w-full justify-center">Open Documentation</a>
                    @if ($alreadyInstalled && $canReinstall)
                        <div class="rounded-[1.5rem] border px-4 py-4 text-sm" style="border-color: rgba(245, 158, 11, 0.26); background: rgba(245, 158, 11, 0.08); color: rgba(255, 243, 224, 0.94);">
                            <p class="font-semibold uppercase tracking-[0.18em]">Local reinstall mode</p>
                            <p class="mt-2 leading-6">This local environment already contains users, so the installer stays available for controlled retesting.</p>
                        </div>
                    @endif
                </div>

                <div class="mt-8 rounded-[1.5rem] border p-4" style="border-color: rgba(255,255,255,0.06); background: rgba(255,255,255,0.03);">
                    <div class="flex items-center justify-between gap-3 text-xs uppercase tracking-[0.24em]" style="color: rgba(226, 232, 240, 0.58);">
                        <span x-text="`Step ${step} of ${maxStep}`"></span>
                        <span x-text="stepLabels[step - 1]"></span>
                    </div>
                    <div class="mt-3 h-2 overflow-hidden rounded-full" style="background: rgba(255,255,255,0.08);">
                        <div class="h-full rounded-full transition-all duration-300" style="background: linear-gradient(90deg, rgba(245, 208, 164, 1) 0%, rgba(245, 158, 11, 1) 100%);" :style="`width: ${progressWidth}`"></div>
                    </div>
                    <div class="mt-5 space-y-2">
                        @foreach (['Requirements', 'Application', 'Database', 'Tenancy', 'Administrator'] as $wizardStep => $wizardLabel)
                            <button type="button" class="flex w-full items-center justify-between rounded-[1rem] border px-4 py-3 text-left transition" style="border-color: rgba(255,255,255,0.06); background: rgba(255,255,255,0.03);" :style="isActive({{ $wizardStep + 1 }}) ? 'border-color: rgba(245, 208, 164, 0.45); background: rgba(245, 208, 164, 0.08);' : (isComplete({{ $wizardStep + 1 }}) ? 'border-color: rgba(16, 185, 129, 0.34); background: rgba(16, 185, 129, 0.08);' : '')" @click="goToStep({{ $wizardStep + 1 }})">
                                <span>
                                    <span class="block text-[11px] font-semibold uppercase tracking-[0.24em]" style="color: rgba(148, 163, 184, 0.68);">0{{ $wizardStep + 1 }}</span>
                                    <span class="mt-1 block text-sm font-semibold">{{ $wizardLabel }}</span>
                                </span>
                                <span class="text-xs font-semibold uppercase tracking-[0.18em]" style="color: rgba(226, 232, 240, 0.56);" x-text="isComplete({{ $wizardStep + 1 }}) ? 'Done' : (isActive({{ $wizardStep + 1 }}) ? 'Now' : 'Next')"></span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="mt-6 rounded-[1.5rem] border p-4" style="border-color: rgba(255,255,255,0.06); background: rgba(255,255,255,0.03);">
                    <p class="text-xs font-semibold uppercase tracking-[0.28em]" style="color: rgba(148, 163, 184, 0.72);">Standard commands</p>
                    <div class="mt-4 space-y-2 text-sm">
                        <div class="rounded-xl border px-3 py-2" style="border-color: rgba(255,255,255,0.06); background: rgba(7, 17, 29, 0.45);">php artisan install:requirements</div>
                        <div class="rounded-xl border px-3 py-2" style="border-color: rgba(255,255,255,0.06); background: rgba(7, 17, 29, 0.45);">php artisan system:setup</div>
                        <div class="rounded-xl border px-3 py-2" style="border-color: rgba(255,255,255,0.06); background: rgba(7, 17, 29, 0.45);">php artisan optimize:clear</div>
                    </div>
                </div>
            </aside>

            <section
                class="rounded-[2rem] border p-5 shadow-2xl sm:p-7"
                style="border-color: rgba(255,255,255,0.08); background: rgba(7, 17, 29, 0.82); backdrop-filter: blur(18px);"
                x-data='installerWizard({ initialStep: {{ $initialStep }}, requirementsPass: @json($requirementsSummary["passes"]), stepLabels: ["Requirements", "Application", "Database", "Tenancy", "Administrator"], databaseTestEndpoint: @json(route("install.test-database", [], false)) })'
            >
                <div class="flex flex-col gap-3 border-b pb-5 sm:flex-row sm:items-center sm:justify-between" style="border-color: rgba(255,255,255,0.08);">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.28em]" style="color: rgba(148, 163, 184, 0.72);">Installation workspace</p>
                        <h2 class="mt-2 text-2xl font-semibold" x-text="stepLabels[step - 1]"></h2>
                    </div>
                    <a href="{{ route('documentation.index') }}" class="text-sm font-semibold" style="color: rgba(245, 208, 164, 0.9);">Need help? Open docs</a>
                </div>

                <form method="POST" action="{{ route('install.store') }}" class="mt-6 space-y-6">
                    @csrf

                    <div x-show="isActive(1)" x-cloak class="space-y-5">
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div class="rounded-[1.5rem] border p-4" style="border-color: rgba(255,255,255,0.06); background: rgba(255,255,255,0.03);">
                                <p class="text-xs uppercase tracking-[0.24em]" style="color: rgba(148, 163, 184, 0.72);">Runtime</p>
                                <p class="mt-2 text-2xl font-semibold">{{ collect($requirementsByCategory->get('PHP Runtime', []))->where('passes', true)->count() }}/{{ collect($requirementsByCategory->get('PHP Runtime', []))->count() }}</p>
                                <p class="mt-2 text-sm" style="color: rgba(226, 232, 240, 0.64);">PHP version checks</p>
                            </div>
                            <div class="rounded-[1.5rem] border p-4" style="border-color: rgba(255,255,255,0.06); background: rgba(255,255,255,0.03);">
                                <p class="text-xs uppercase tracking-[0.24em]" style="color: rgba(148, 163, 184, 0.72);">Extensions</p>
                                <p class="mt-2 text-2xl font-semibold">{{ collect($requirementsByCategory->get('PHP Extension', []))->where('passes', true)->count() }}/{{ collect($requirementsByCategory->get('PHP Extension', []))->count() }}</p>
                                <p class="mt-2 text-sm" style="color: rgba(226, 232, 240, 0.64);">Required modules loaded</p>
                            </div>
                            <div class="rounded-[1.5rem] border p-4" style="border-color: rgba(255,255,255,0.06); background: rgba(255,255,255,0.03);">
                                <p class="text-xs uppercase tracking-[0.24em]" style="color: rgba(148, 163, 184, 0.72);">Permissions</p>
                                <p class="mt-2 text-2xl font-semibold">{{ collect($requirementsByCategory->get('Permissions', []))->where('passes', true)->count() }}/{{ collect($requirementsByCategory->get('Permissions', []))->count() }}</p>
                                <p class="mt-2 text-sm" style="color: rgba(226, 232, 240, 0.64);">Writable installer paths</p>
                            </div>
                        </div>

                        @if ($errors->has('requirements'))
                            <div class="rounded-[1.25rem] border px-4 py-3 text-sm" style="border-color: rgba(239, 68, 68, 0.25); background: rgba(239, 68, 68, 0.08); color: rgba(254, 202, 202, 0.95);">
                                {{ $errors->first('requirements') }}
                            </div>
                        @endif

                        @foreach ($requirementsByCategory as $category => $requirements)
                            <div class="rounded-[1.5rem] border p-4" style="border-color: rgba(255,255,255,0.06); background: rgba(255,255,255,0.03);">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-semibold">{{ $category }}</p>
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em]" style="background: {{ collect($requirements)->every('passes') ? 'rgba(16, 185, 129, 0.12)' : 'rgba(239, 68, 68, 0.12)' }}; color: {{ collect($requirements)->every('passes') ? 'rgba(167, 243, 208, 0.96)' : 'rgba(254, 202, 202, 0.96)' }};">{{ collect($requirements)->every('passes') ? 'Pass' : 'Needs attention' }}</span>
                                </div>
                                <div class="mt-4 grid gap-3 lg:grid-cols-2">
                                    @foreach ($requirements as $requirement)
                                        <div class="rounded-[1.25rem] border px-4 py-4" style="border-color: rgba(255,255,255,0.06); background: rgba(7, 17, 29, 0.35);">
                                            <div class="flex items-center justify-between gap-3">
                                                <p class="text-sm font-semibold">{{ $requirement['label'] }}</p>
                                                <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em]" style="background: {{ $requirement['passes'] ? 'rgba(16, 185, 129, 0.12)' : 'rgba(239, 68, 68, 0.12)' }}; color: {{ $requirement['passes'] ? 'rgba(167, 243, 208, 0.96)' : 'rgba(254, 202, 202, 0.96)' }};">{{ $requirement['passes'] ? 'Pass' : 'Fail' }}</span>
                                            </div>
                                            <p class="mt-2 text-xs" style="color: rgba(148, 163, 184, 0.74);">Expected {{ $requirement['expected'] }} | Current {{ $requirement['current'] }}</p>
                                            <p class="mt-3 text-sm leading-6" style="color: rgba(226, 232, 240, 0.66);">{{ $requirement['help'] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        @if ($canReinstall)
                            <div class="rounded-[1.5rem] border p-4" style="border-color: rgba(255,255,255,0.06); background: rgba(255,255,255,0.03);">
                                <label class="flex items-start gap-3">
                                    <input type="checkbox" name="install_fresh" value="1" @checked(old('install_fresh')) class="mt-1 rounded border-slate-400 text-amber-500 focus:ring-amber-400">
                                    <span>
                                        <span class="block text-sm font-semibold">Run a fresh install</span>
                                        <span class="mt-1 block text-sm leading-6" style="color: rgba(226, 232, 240, 0.66);">Local testing only. This runs migrate:fresh and removes existing data.</span>
                                    </span>
                                </label>
                                <x-input-error :messages="$errors->get('install_fresh')" class="mt-2" />
                            </div>
                        @endif
                    </div>

                    <div x-show="isActive(2)" x-cloak class="grid gap-5 lg:grid-cols-2">
                        <div class="rounded-[1.5rem] border p-5" style="border-color: rgba(255,255,255,0.06); background: rgba(255,255,255,0.03);">
                            <label for="project_title" class="text-sm font-semibold">Project title</label>
                            <input id="project_title" name="project_title" type="text" class="form-input mt-3" value="{{ old('project_title', $defaults['project_title']) }}" required>
                            <x-input-error :messages="$errors->get('project_title')" class="mt-2" />
                        </div>
                        <div class="rounded-[1.5rem] border p-5" style="border-color: rgba(255,255,255,0.06); background: rgba(255,255,255,0.03);">
                            <label for="app_url" class="text-sm font-semibold">Application URL</label>
                            <input id="app_url" name="app_url" type="url" class="form-input mt-3" value="{{ old('app_url', $defaults['app_url']) }}" required>
                            <x-input-error :messages="$errors->get('app_url')" class="mt-2" />
                        </div>
                    </div>

                    <div x-show="isActive(3)" x-cloak class="space-y-5">
                        <div class="grid gap-5 lg:grid-cols-2">
                            <div class="rounded-[1.5rem] border p-5" style="border-color: rgba(255,255,255,0.06); background: rgba(255,255,255,0.03);">
                                <label for="db_host" class="text-sm font-semibold">Database host</label>
                                <input id="db_host" name="db_host" type="text" class="form-input mt-3" value="{{ old('db_host', $defaults['db_host']) }}" required>
                                <x-input-error :messages="$errors->get('db_host')" class="mt-2" />
                            </div>
                            <div class="rounded-[1.5rem] border p-5" style="border-color: rgba(255,255,255,0.06); background: rgba(255,255,255,0.03);">
                                <label for="db_port" class="text-sm font-semibold">Database port</label>
                                <input id="db_port" name="db_port" type="number" class="form-input mt-3" value="{{ old('db_port', $defaults['db_port']) }}" required>
                                <x-input-error :messages="$errors->get('db_port')" class="mt-2" />
                            </div>
                            <div class="rounded-[1.5rem] border p-5" style="border-color: rgba(255,255,255,0.06); background: rgba(255,255,255,0.03);">
                                <label for="db_database" class="text-sm font-semibold">Database name</label>
                                <input id="db_database" name="db_database" type="text" class="form-input mt-3" value="{{ old('db_database', $defaults['db_database']) }}" required>
                                <x-input-error :messages="$errors->get('db_database')" class="mt-2" />
                            </div>
                            <div class="rounded-[1.5rem] border p-5" style="border-color: rgba(255,255,255,0.06); background: rgba(255,255,255,0.03);">
                                <label for="db_username" class="text-sm font-semibold">Database user</label>
                                <input id="db_username" name="db_username" type="text" class="form-input mt-3" value="{{ old('db_username', $defaults['db_username']) }}" required>
                                <x-input-error :messages="$errors->get('db_username')" class="mt-2" />
                            </div>
                            <div class="rounded-[1.5rem] border p-5 lg:col-span-2" style="border-color: rgba(255,255,255,0.06); background: rgba(255,255,255,0.03);">
                                <label for="db_password" class="text-sm font-semibold">Database password</label>
                                <input id="db_password" name="db_password" type="password" class="form-input mt-3" value="{{ old('db_password', $defaults['db_password']) }}">
                                <x-input-error :messages="$errors->get('db_password')" class="mt-2" />
                            </div>
                        </div>

                        <div class="rounded-[1.5rem] border p-5" style="border-color: rgba(255,255,255,0.06); background: rgba(255,255,255,0.03);">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm font-semibold">Live database test</p>
                                    <p class="mt-1 text-sm" style="color: rgba(226, 232, 240, 0.64);">Check these credentials before you continue.</p>
                                </div>
                                <button type="button" class="btn-secondary w-full sm:w-auto" @click="testDatabaseConnection($el.form)" :disabled="databaseStatus.state === 'testing'" :class="databaseStatus.state === 'testing' ? 'opacity-60 cursor-not-allowed' : ''">
                                    <span x-show="databaseStatus.state !== 'testing'" x-cloak>Test connection</span>
                                    <span x-show="databaseStatus.state === 'testing'" x-cloak>Testing...</span>
                                </button>
                            </div>
                            <div class="mt-4 rounded-[1.25rem] border px-4 py-3 text-sm" :style="databaseStatus.state === 'passed' ? 'border-color: rgba(16, 185, 129, 0.25); background: rgba(16, 185, 129, 0.08); color: rgba(167, 243, 208, 0.96);' : (databaseStatus.state === 'failed' ? 'border-color: rgba(239, 68, 68, 0.25); background: rgba(239, 68, 68, 0.08); color: rgba(254, 202, 202, 0.96);' : 'border-color: rgba(255,255,255,0.06); background: rgba(7, 17, 29, 0.35); color: rgba(226, 232, 240, 0.68);')">
                                <p x-text="databaseStatus.message"></p>
                            </div>
                        </div>
                    </div>

                    <div x-show="isActive(4)" x-cloak class="space-y-5">
                        <div class="rounded-[1.5rem] border p-5" style="border-color: rgba(255,255,255,0.06); background: rgba(255,255,255,0.03);">
                            <label for="tenancy_base_domain" class="text-sm font-semibold">Base domain</label>
                            <input id="tenancy_base_domain" name="tenancy_base_domain" type="text" class="form-input mt-3" value="{{ old('tenancy_base_domain', $defaults['tenancy_base_domain']) }}" placeholder="eelo-university.test">
                            <p class="mt-3 text-sm" style="color: rgba(226, 232, 240, 0.64);">Use the shared host for tenant subdomains.</p>
                            <x-input-error :messages="$errors->get('tenancy_base_domain')" class="mt-2" />
                        </div>
                        <div class="rounded-[1.5rem] border p-5" style="border-color: rgba(255,255,255,0.06); background: rgba(255,255,255,0.03);">
                            <label for="default_company_name" class="text-sm font-semibold">Default company name</label>
                            <input id="default_company_name" name="default_company_name" type="text" class="form-input mt-3" value="{{ old('default_company_name', $defaults['default_company_name']) }}" required>
                            <p class="mt-3 text-sm" style="color: rgba(226, 232, 240, 0.64);">A domain can be assigned later from company management.</p>
                            <x-input-error :messages="$errors->get('default_company_name')" class="mt-2" />
                        </div>
                    </div>

                    <div x-show="isActive(5)" x-cloak class="space-y-5">
                        <div class="grid gap-5 lg:grid-cols-2">
                            <div class="rounded-[1.5rem] border p-5" style="border-color: rgba(255,255,255,0.06); background: rgba(255,255,255,0.03);">
                                <label for="admin_name" class="text-sm font-semibold">Administrator name</label>
                                <input id="admin_name" name="admin_name" type="text" class="form-input mt-3" value="{{ old('admin_name', $defaults['admin_name']) }}" required>
                                <x-input-error :messages="$errors->get('admin_name')" class="mt-2" />
                            </div>
                            <div class="rounded-[1.5rem] border p-5" style="border-color: rgba(255,255,255,0.06); background: rgba(255,255,255,0.03);">
                                <label for="admin_email" class="text-sm font-semibold">Administrator email</label>
                                <input id="admin_email" name="admin_email" type="email" class="form-input mt-3" value="{{ old('admin_email', $defaults['admin_email']) }}" required>
                                <x-input-error :messages="$errors->get('admin_email')" class="mt-2" />
                            </div>
                            <div class="rounded-[1.5rem] border p-5" style="border-color: rgba(255,255,255,0.06); background: rgba(255,255,255,0.03);">
                                <label for="admin_password" class="text-sm font-semibold">Password</label>
                                <input id="admin_password" name="admin_password" type="password" class="form-input mt-3" required>
                                <x-input-error :messages="$errors->get('admin_password')" class="mt-2" />
                            </div>
                            <div class="rounded-[1.5rem] border p-5" style="border-color: rgba(255,255,255,0.06); background: rgba(255,255,255,0.03);">
                                <label for="admin_password_confirmation" class="text-sm font-semibold">Confirm password</label>
                                <input id="admin_password_confirmation" name="admin_password_confirmation" type="password" class="form-input mt-3" required>
                            </div>
                        </div>

                        <div class="rounded-[1.5rem] border p-5" style="border-color: rgba(255,255,255,0.06); background: rgba(255,255,255,0.03);">
                            <p class="text-xs font-semibold uppercase tracking-[0.24em]" style="color: rgba(148, 163, 184, 0.72);">Final review</p>
                            <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                <div class="rounded-[1rem] border px-4 py-4" style="border-color: rgba(255,255,255,0.06); background: rgba(7, 17, 29, 0.35);">
                                    <p class="text-xs uppercase tracking-[0.2em]" style="color: rgba(148, 163, 184, 0.72);">Requirements</p>
                                    <p class="mt-2 text-sm font-semibold">{{ $requirementsSummary['passes'] ? 'Passed' : $requirementFailures.' failing' }}</p>
                                </div>
                                <div class="rounded-[1rem] border px-4 py-4" style="border-color: rgba(255,255,255,0.06); background: rgba(7, 17, 29, 0.35);">
                                    <p class="text-xs uppercase tracking-[0.2em]" style="color: rgba(148, 163, 184, 0.72);">Database</p>
                                    <p class="mt-2 text-sm font-semibold">{{ old('db_database', $defaults['db_database']) ?: 'Not set' }}</p>
                                </div>
                                <div class="rounded-[1rem] border px-4 py-4" style="border-color: rgba(255,255,255,0.06); background: rgba(7, 17, 29, 0.35);">
                                    <p class="text-xs uppercase tracking-[0.2em]" style="color: rgba(148, 163, 184, 0.72);">Base domain</p>
                                    <p class="mt-2 text-sm font-semibold">{{ old('tenancy_base_domain', $defaults['tenancy_base_domain']) ?: 'Optional' }}</p>
                                </div>
                                <div class="rounded-[1rem] border px-4 py-4" style="border-color: rgba(255,255,255,0.06); background: rgba(7, 17, 29, 0.35);">
                                    <p class="text-xs uppercase tracking-[0.2em]" style="color: rgba(148, 163, 184, 0.72);">Connection test</p>
                                    <p class="mt-2 text-sm font-semibold" x-text="databaseStatus.state === 'idle' ? 'Not run yet' : databaseStatus.message"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 border-t pt-5 sm:flex-row sm:items-center sm:justify-between" style="border-color: rgba(255,255,255,0.08);">
                        <p class="text-sm" style="color: rgba(226, 232, 240, 0.66);">Complete the checks, confirm the database, then finish installation.</p>
                        <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row">
                            <button type="button" class="btn-secondary w-full sm:w-auto" x-show="step > 1" x-cloak @click="previousStep()">Previous</button>
                            <button type="button" class="btn-primary w-full sm:w-auto" x-show="step < maxStep" x-cloak @click="nextStep()" :disabled="!canAdvance" :class="!canAdvance ? 'opacity-60 cursor-not-allowed' : ''">Next</button>
                            <button type="submit" class="btn-primary w-full sm:w-auto" x-show="step === maxStep" x-cloak>Install platform</button>
                        </div>
                    </div>
                </form>
            </section>
        </section>
    </main>
</body>
</html>