<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DocumentationController extends Controller
{
    public function index(): View
    {
        $guides = collect([
            [
                'id' => 'overview',
                'title' => 'Overview',
                'summary' => 'Platform scope, architecture, terminology, and core services.',
                'path' => 'docs/overview.md',
            ],
            [
                'id' => 'installation',
                'title' => 'Installation',
                'summary' => 'Browser installer, CLI bootstrap, server requirements, and post-install checks.',
                'path' => 'docs/installation.md',
            ],
            [
                'id' => 'deployment',
                'title' => 'Deployment',
                'summary' => 'Real VPS checklist, production .env values, and Apache or Nginx configuration.',
                'path' => 'docs/deployment.md',
            ],
            [
                'id' => 'usage',
                'title' => 'Usage',
                'summary' => 'Day-to-day administration, dashboard behavior, onboarding, and exports.',
                'path' => 'docs/usage.md',
            ],
            [
                'id' => 'tenancy',
                'title' => 'Tenancy',
                'summary' => 'Base domain rules, tenant host normalization, sessions, and context switching.',
                'path' => 'docs/tenancy.md',
            ],
            [
                'id' => 'operations',
                'title' => 'Operations',
                'summary' => 'Deployment, maintenance, builds, cache management, and release checks.',
                'path' => 'docs/operations.md',
            ],
            [
                'id' => 'troubleshooting',
                'title' => 'Troubleshooting',
                'summary' => 'Common installer, login, tenancy, and frontend recovery paths.',
                'path' => 'docs/troubleshooting.md',
            ],
        ])->map(function (array $guide): array {
            $markdown = File::get(base_path($guide['path']));
            $wordCount = str_word_count(strip_tags(Str::markdown($markdown)));

            return [
                ...$guide,
                'reading_time' => max(1, (int) ceil($wordCount / 220)),
                'html' => Str::markdown($markdown, [
                    'html_input' => 'strip',
                    'allow_unsafe_links' => false,
                ]),
            ];
        })->all();

        $screenTour = [
            [
                'title' => 'Installer Wizard',
                'caption' => 'Requirements check, database test, tenancy setup, and first Super Admin bootstrap.',
                'url' => route('install.create'),
                'visibility' => 'Public route',
            ],
            [
                'title' => 'Sign In',
                'caption' => 'Authentication screen for Super Admin and tenant users on the shared domain.',
                'url' => route('login'),
                'visibility' => 'Public route',
            ],
            [
                'title' => 'Registration',
                'caption' => 'Public organization onboarding flow for creating a company and its first admin.',
                'url' => route('register'),
                'visibility' => 'Public route',
            ],
        ];

        if (auth()->check()) {
            $screenTour[] = [
                'title' => 'Workspace Entry',
                'caption' => 'The authenticated workspace landing route used after login.',
                'url' => route('dashboard'),
                'visibility' => 'Signed-in users',
            ];

            if (auth()->user()->can('read-setting')) {
                $screenTour[] = [
                    'title' => 'Settings Center',
                    'caption' => 'Branding, theme, base-domain, dashboard, export, and template configuration.',
                    'url' => route('admin.settings.index'),
                    'visibility' => 'Signed-in users with settings access',
                ];
            }
        }

        return view('documentation.index', [
            'guides' => $guides,
            'screenTour' => $screenTour,
            'commands' => [
                'php artisan install:requirements',
                'php artisan migrate',
                'php artisan system:setup',
                'php artisan system:setup --refresh-passwords',
                'php artisan optimize:clear',
                'php artisan test',
                'npm run build',
            ],
        ]);
    }
}