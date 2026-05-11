<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Services\TenancyDomainService;
use App\Support\Tenancy\CurrentCompanyContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class SetCompanyContext
{
    public function __construct(protected TenancyDomainService $tenancyDomainService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $selectedCompanyId = $request->hasSession()
            ? $request->session()->get('selected_company_id')
            : null;
        $domainCompanyId = $this->resolveCompanyIdFromDomain($request);

        if (! $user && $domainCompanyId !== null) {
            app(CurrentCompanyContext::class)->initializeFromUser(null, $domainCompanyId);
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return $next($request);
        }

        if ($selectedCompanyId === null && $domainCompanyId !== null && $user?->isSuperAdmin()) {
            $selectedCompanyId = $domainCompanyId;
        }

        if ($user?->isSuperAdmin() && $selectedCompanyId !== null) {
            $selectedCompany = Company::query()->find($selectedCompanyId);

            if (! $selectedCompany) {
                if ($request->hasSession()) {
                    $request->session()->forget('selected_company_id');
                }

                $selectedCompanyId = null;
            }
        }

        app(CurrentCompanyContext::class)->initializeFromUser($user, $selectedCompanyId);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $next($request);
    }

    protected function resolveCompanyIdFromDomain(Request $request): ?int
    {
        if (! config('tenancy.resolve_by_domain', false)) {
            return null;
        }

        if (! Schema::hasTable('companies')) {
            return null;
        }

        $host = strtolower((string) ($request->server('HTTP_HOST') ?: $request->getHost()));
        $host = explode(':', $host)[0];

        if ($host === '' || in_array($host, ['localhost', '127.0.0.1'], true)) {
            return null;
        }

        if ($this->tenancyDomainService->isBaseDomainHost($host)) {
            return null;
        }

        return Company::query()
            ->where('status', 'active')
            ->whereRaw('LOWER(domain) = ?', [$host])
            ->value('id');
    }
}
