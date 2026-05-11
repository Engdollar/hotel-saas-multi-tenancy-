<?php

namespace App\Providers;

use App\Domain\Accounting\Contracts\JournalEntryRepository;
use App\Domain\Accounting\Listeners\PostReservationRevenueEntry;
use App\Domain\Accounting\Repositories\EloquentJournalEntryRepository;
use App\Domain\Hotel\Contracts\ReservationRepository;
use App\Domain\Hotel\Events\ReservationConfirmed;
use App\Domain\Hotel\Repositories\EloquentReservationRepository;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Setting;
use App\Models\SupportTicket;
use App\Models\User;
use App\Policies\PermissionPolicy;
use App\Policies\RolePolicy;
use App\Policies\SettingPolicy;
use App\Policies\SupportTicketPolicy;
use App\Policies\UserPolicy;
use App\Services\SettingsService;
use App\Services\TenantWorkspaceService;
use App\Support\Tenancy\CurrentCompanyContext;
use App\Support\Tenancy\QueueTenantContext;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CurrentCompanyContext::class, fn () => new CurrentCompanyContext());
        $this->app->singleton(QueueTenantContext::class, fn () => new QueueTenantContext());
        $this->app->bind(ReservationRepository::class, EloquentReservationRepository::class);
        $this->app->bind(JournalEntryRepository::class, EloquentJournalEntryRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->normalizeLocalSessionDomain();
        $this->removeStaleViteHotFile();
        $this->registerQueueTenantContext();
        $this->registerDomainEvents();

        Gate::before(function (User $user) {
            return $user->isSuperAdmin() ? true : null;
        });

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
        Gate::policy(Setting::class, SettingPolicy::class);
        Gate::policy(SupportTicket::class, SupportTicketPolicy::class);

        View::composer('*', function ($view) {
            $settingsService = app(SettingsService::class);
            $context = app(CurrentCompanyContext::class);

            $activeCompany = $context->id()
                ? Company::query()->find($context->id())
                : null;

            $availableCompanies = auth()->check() && auth()->user()->isSuperAdmin()
                ? Company::query()->orderBy('name')->get(['id', 'name'])
                : collect();

            $view->with('appSettings', $settingsService->all());
            $view->with('appThemePresetStyles', $settingsService->themePresetStyles());
            $view->with('activeCompany', $activeCompany);
            $view->with('availableCompanies', $availableCompanies);
            $view->with('workspaceLabel', app(TenantWorkspaceService::class)->workspaceLabel(auth()->user()));
            $view->with('tenantWorkspaceNavigation', auth()->check()
                ? app(TenantWorkspaceService::class)->navigation(auth()->user())
                : []);
        });
    }

    protected function removeStaleViteHotFile(): void
    {
        $hotFile = public_path('hot');

        if (! is_file($hotFile)) {
            return;
        }

        $url = trim((string) @file_get_contents($hotFile));

        if ($url === '' || $this->canReachViteHotServer($url)) {
            return;
        }

        @unlink($hotFile);
    }

    protected function normalizeLocalSessionDomain(): void
    {
        if (! $this->app->bound('request')) {
            return;
        }

        $host = request()->getHost();

        if (! $this->isLocalSessionHost($host)) {
            return;
        }

        config(['session.domain' => null]);
    }

    protected function isLocalSessionHost(string $host): bool
    {
        return $host === 'localhost'
            || $host === '127.0.0.1'
            || filter_var($host, FILTER_VALIDATE_IP) !== false;
    }

    protected function canReachViteHotServer(string $url): bool
    {
        $parts = parse_url($url);

        if (! is_array($parts)) {
            return false;
        }

        $host = trim((string) ($parts['host'] ?? ''), '[]');
        $port = (int) ($parts['port'] ?? 0);

        if ($host === '' || $port <= 0) {
            return false;
        }

        try {
            $connection = @fsockopen($host, $port, $errorCode, $errorMessage, 0.2);

            if (! is_resource($connection)) {
                return false;
            }

            fclose($connection);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    protected function registerQueueTenantContext(): void
    {
        Queue::createPayloadUsing(function () {
            return app(QueueTenantContext::class)->buildPayload(app(CurrentCompanyContext::class));
        });

        Queue::before(function (JobProcessing $event) {
            app(QueueTenantContext::class)->applyPayload(
                app(CurrentCompanyContext::class),
                $event->job->payload(),
            );
        });

        $resetContext = function () {
            app(QueueTenantContext::class)->reset(app(CurrentCompanyContext::class));
        };

        Queue::after(function (JobProcessed $event) use ($resetContext) {
            $resetContext();
        });

        Queue::exceptionOccurred(function (JobExceptionOccurred $event) use ($resetContext) {
            $resetContext();
        });
    }

    protected function registerDomainEvents(): void
    {
        Event::listen(ReservationConfirmed::class, PostReservationRevenueEntry::class);
    }
}
