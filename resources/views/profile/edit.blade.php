<x-app-layout>
    @php
        $profileUser = $user;
        $profileRoles = $profileUser->roles->pluck('name')->filter()->values();
        $profileJoinedAt = $profileUser->created_at?->format('M Y') ?? 'Recently';
        $profileVerified = ! is_null($profileUser->email_verified_at);
        $profileHandle = '@'.str((string) $profileUser->email)->before('@')->replace([' ', '.'], '_');
        $profileCompleteness = (int) round((collect([
            filled($profileUser->name),
            filled($profileUser->email),
            filled($profileUser->profile_image_path),
            $profileVerified,
        ])->filter()->count() / 4) * 100);
    @endphp

    <x-slot name="header">
        <div>
            <h1 class="type-page-title" style="color: var(--text-primary);">Profile</h1>
            <p class="mt-1 text-sm text-muted">Manage your identity, account access, and security settings.</p>
        </div>
    </x-slot>

    <div class="profile-page space-y-6">
        <section id="profile-overview" class="profile-showcase panel">
            <div class="profile-showcase-cover">
                <div class="profile-showcase-orb is-one"></div>
                <div class="profile-showcase-orb is-two"></div>
                <div class="profile-showcase-orb is-three"></div>

                <div class="profile-cover-chrome" aria-hidden="true">
                    <div class="profile-cover-badge">
                        <span class="profile-cover-badge-icon"><x-icon name="sparkles" class="h-4 w-4" /></span>
                        <div>
                            <span class="profile-cover-badge-label">Profile scene</span>
                            <p class="profile-cover-badge-text">Identity, access, and trust</p>
                        </div>
                    </div>

                    <div class="profile-cover-meta">
                        <span class="profile-cover-pill is-live">Live profile</span>
                        <span class="profile-cover-pill">{{ $profileVerified ? 'Verified' : 'Pending review' }}</span>
                    </div>
                </div>

                <div class="profile-cover-brand" aria-hidden="true">
                    <div class="profile-cover-brand-mark">
                        @if ($appSettings->get('logo'))
                                <img src="{{ \App\Support\AssetPath::storageUrl($appSettings->get('logo')) }}" alt="{{ $appSettings->get('project_title', config('app.name')) }}" class="h-10 w-10 rounded-2xl object-contain">
                        @else
                            <x-application-logo />
                        @endif
                    </div>
                    <div class="min-w-0">
                        <span class="profile-cover-badge-label">Profile Settings</span>
                        <p class="profile-cover-badge-text">{{ $appSettings->get('project_title', config('app.name', 'Laravel')) }}</p>
                    </div>
                </div>

                <div class="profile-showcase-illustration" aria-hidden="true">
                    <svg viewBox="0 0 720 260" class="w-full" role="img" aria-label="Animated profile illustration">
                        <defs>
                            <linearGradient id="profile-cover-panel" x1="124" y1="42" x2="530" y2="220" gradientUnits="userSpaceOnUse">
                                <stop offset="0" stop-color="var(--panel-bg)" stop-opacity="0.96" />
                                <stop offset="1" stop-color="var(--panel-soft)" stop-opacity="0.92" />
                            </linearGradient>
                            <linearGradient id="profile-cover-accent" x1="160" y1="88" x2="428" y2="248" gradientUnits="userSpaceOnUse">
                                <stop offset="0" stop-color="var(--accent)" stop-opacity="0.94" />
                                <stop offset="1" stop-color="var(--accent-strong)" stop-opacity="0.86" />
                            </linearGradient>
                        </defs>

                        <circle cx="88" cy="56" r="26" fill="var(--panel-bg)" opacity="0.16">
                            <animate attributeName="cy" values="56;72;56" dur="6.2s" repeatCount="indefinite" />
                        </circle>
                        <circle cx="612" cy="64" r="18" fill="var(--panel-bg)" opacity="0.18">
                            <animate attributeName="cx" values="612;590;612" dur="5.6s" repeatCount="indefinite" />
                        </circle>
                        <circle cx="648" cy="192" r="24" fill="var(--accent-contrast)" opacity="0.12">
                            <animate attributeName="cy" values="192;208;192" dur="6.8s" repeatCount="indefinite" />
                        </circle>

                        <g opacity="0.92">
                            <animateTransform attributeName="transform" type="translate" values="0 0; 4 -3; 0 0" dur="8s" repeatCount="indefinite" />
                            <rect x="142" y="42" width="368" height="182" rx="34" fill="url(#profile-cover-panel)" stroke="var(--panel-border)" stroke-width="2" />
                            <rect x="172" y="68" width="128" height="12" rx="6" fill="var(--text-primary)" opacity="0.13" />
                            <rect x="172" y="92" width="186" height="9" rx="4.5" fill="var(--panel-border)" opacity="0.82" />
                            <rect x="172" y="108" width="144" height="8" rx="4" fill="var(--panel-border)" opacity="0.56" />
                        </g>

                        <g>
                            <animateTransform attributeName="transform" type="translate" values="0 0; -3 3; 0 0" dur="7.2s" repeatCount="indefinite" />
                            <rect x="176" y="128" width="212" height="72" rx="26" fill="url(#profile-cover-accent)">
                                <animateTransform attributeName="transform" type="translate" values="0 0; 0 -5; 0 0" dur="4.8s" repeatCount="indefinite" />
                            </rect>
                            <rect x="198" y="146" width="60" height="60" rx="22" fill="var(--accent-contrast)" opacity="0.96" />
                            <text x="228" y="184" text-anchor="middle" font-size="26" font-weight="800" fill="var(--accent-strong)">{{ $profileUser->initials ?: 'U' }}</text>
                            <rect x="278" y="148" width="80" height="10" rx="5" fill="var(--accent-contrast)" opacity="0.84" />
                            <rect x="278" y="168" width="94" height="8" rx="4" fill="var(--accent-contrast)" opacity="0.46" />
                            <rect x="278" y="184" width="66" height="8" rx="4" fill="var(--accent-contrast)" opacity="0.34" />
                        </g>

                        <g opacity="0.94">
                            <rect x="432" y="74" width="122" height="84" rx="24" fill="var(--panel-bg)" stroke="var(--panel-border)" stroke-width="2">
                                <animateTransform attributeName="transform" type="translate" values="0 0; 0 7; 0 0" dur="5.4s" repeatCount="indefinite" />
                            </rect>
                            <circle cx="468" cy="106" r="14" fill="var(--accent)" opacity="0.16" />
                            <path d="M462 106l5 5 10-12" stroke="var(--accent)" stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round" />
                            <rect x="490" y="98" width="40" height="8" rx="4" fill="var(--text-primary)" opacity="0.18" />
                            <rect x="490" y="114" width="32" height="7" rx="3.5" fill="var(--text-primary)" opacity="0.1" />
                            <rect x="454" y="134" width="76" height="8" rx="4" fill="var(--panel-border)" opacity="0.8" />
                        </g>

                        <g opacity="0.96">
                            <rect x="468" y="168" width="142" height="52" rx="22" fill="var(--panel-bg)" stroke="var(--panel-border)" stroke-width="2">
                                <animateTransform attributeName="transform" type="translate" values="0 0; 0 -6; 0 0" dur="6s" repeatCount="indefinite" />
                            </rect>
                            <rect x="488" y="184" width="38" height="8" rx="4" fill="var(--accent)" opacity="0.34">
                                <animate attributeName="width" values="38;56;38" dur="3.6s" repeatCount="indefinite" />
                                <animate attributeName="opacity" values="0.34;0.62;0.34" dur="3.2s" repeatCount="indefinite" />
                            </rect>
                            <rect x="488" y="198" width="88" height="8" rx="4" fill="var(--text-primary)" opacity="0.12" />
                            <rect x="582" y="182" width="14" height="14" rx="7" fill="var(--accent)" opacity="0.82">
                                <animate attributeName="opacity" values="0.82;0.42;0.82" dur="2.6s" repeatCount="indefinite" />
                            </rect>
                        </g>
                    </svg>
                </div>
            </div>

            <div class="profile-showcase-body">
                <div class="profile-showcase-main sm:pt-6">
                    <div class="profile-showcase-avatar ">
                        <div class="profile-avatar-shell is-hero">
                            <x-avatar :user="$profileUser" size="h-32 w-32 rounded-[2.15rem] sm:h-36 sm:w-36" textSize="text-4xl" />
                        </div>
                    </div>

                    <div class="min-w-0 flex-1 pt-3 sm:pt-4">
                        <p class="section-kicker">Profile Hub</p>
                        <h2 class="profile-showcase-title">{{ $profileUser->name }}</h2>
                        <p class="profile-showcase-subtitle">{{ $profileHandle }} · {{ $profileUser->email }}</p>

                        <div class="profile-showcase-pills">
                            <span class="profile-meta-pill">Member since {{ $profileJoinedAt }}</span>
                            <span class="profile-meta-pill">{{ $profileVerified ? 'Verified account' : 'Verification pending' }}</span>
                            <span class="profile-meta-pill">{{ $profileRoles->count() ? $profileRoles->implode(' · ') : 'No roles assigned' }}</span>
                        </div>
                    </div>

                    <div class="profile-showcase-stats">
                        <div class="profile-showcase-stat">
                            <span class="profile-showcase-stat-icon"><x-icon name="sparkles" class="h-4 w-4" /></span>
                            <span class="profile-showcase-stat-label">Completion</span>
                            <strong>{{ $profileCompleteness }}%</strong>
                        </div>
                        <div class="profile-showcase-stat">
                            <span class="profile-showcase-stat-icon"><x-icon name="check-square" class="h-4 w-4" /></span>
                            <span class="profile-showcase-stat-label">Roles</span>
                            <strong>{{ $profileRoles->count() }}</strong>
                        </div>
                        <div class="profile-showcase-stat">
                            <span class="profile-showcase-stat-icon"><x-icon name="settings" class="h-4 w-4" /></span>
                            <span class="profile-showcase-stat-label">Security</span>
                            <strong>{{ $profileVerified ? 'Strong' : 'Pending' }}</strong>
                        </div>
                    </div>
                </div>

                <div class="profile-nav-strip">
                    <a href="#profile-overview" class="profile-nav-pill is-active">About</a>
                    <a href="#profile-identity" class="profile-nav-pill">Identity</a>
                    <a href="#profile-security" class="profile-nav-pill">Security</a>
                    <a href="#profile-account" class="profile-nav-pill">Account</a>
                </div>
            </div>
        </section>

        <div class="profile-layout-grid">
            <aside class="space-y-6 xl:sticky xl:top-24 xl:self-start">
                <div id="profile-about" class="panel p-6 profile-anchor-section">
                    <section class="space-y-5">
                        <header class="profile-sidebar-header">
                            <div>
                                <p class="section-kicker">Intro</p>
                                <h2 class="profile-section-title">About {{ $profileUser->name }}</h2>
                            </div>
                            <span class="profile-section-icon"><x-icon name="user" class="h-4 w-4" /></span>
                        </header>

                        <p class="text-sm text-muted">This is the identity block other administrators see around the workspace. Keep it current and recognizable.</p>

                        <div class="profile-sidebar-stack">
                            <div class="profile-sidebar-card">
                                <span class="profile-mini-icon"><x-icon name="user" class="h-4 w-4" /></span>
                                <div>
                                    <span class="profile-overview-label">Display name</span>
                                    <strong>{{ $profileUser->name }}</strong>
                                </div>
                            </div>
                            <div class="profile-sidebar-card">
                                <span class="profile-mini-icon"><x-icon name="bell" class="h-4 w-4" /></span>
                                <div>
                                    <span class="profile-overview-label">Primary email</span>
                                    <strong class="break-all">{{ $profileUser->email }}</strong>
                                </div>
                            </div>
                            <div class="profile-sidebar-card">
                                <span class="profile-mini-icon"><x-icon name="check-square" class="h-4 w-4" /></span>
                                <div>
                                    <span class="profile-overview-label">Verification</span>
                                    <strong>{{ $profileVerified ? 'Verified account' : 'Waiting for verification' }}</strong>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="panel p-6 profile-anchor-section">
                    <section class="space-y-5">
                        <header class="profile-sidebar-header">
                            <div>
                                <p class="section-kicker">Access</p>
                                <h2 class="profile-section-title">Roles and visibility</h2>
                            </div>
                            <span class="profile-section-icon"><x-icon name="eye" class="h-4 w-4" /></span>
                        </header>

                        <div class="profile-chip-group">
                            @forelse ($profileRoles as $role)
                                <span class="profile-role-chip">{{ $role }}</span>
                            @empty
                                <span class="profile-role-chip">No assigned roles</span>
                            @endforelse
                        </div>

                        <div class="profile-completion-card">
                            <div class="flex items-center justify-between gap-3">
                                <span class="profile-overview-label">Profile completion</span>
                                <strong>{{ $profileCompleteness }}%</strong>
                            </div>
                            <div class="profile-completion-track">
                                <span class="profile-completion-fill" style="width: {{ $profileCompleteness }}%"></span>
                            </div>
                            <p class="text-sm text-muted">A stronger profile photo and verified email make your account easier to trust.</p>
                        </div>
                    </section>
                </div>
            </aside>

            <div class="space-y-6">
                <div id="profile-identity" class="panel p-6 sm:p-8 profile-anchor-section">
                    @include('profile.partials.update-profile-information-form')
                </div>

                <div id="profile-security" class="panel p-6 sm:p-8 profile-anchor-section">
                    @include('profile.partials.update-password-form')
                </div>

                <div id="profile-account" class="panel p-6 sm:p-8 profile-anchor-section">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
