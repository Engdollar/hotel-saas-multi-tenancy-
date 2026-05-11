<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantCompanyIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isSuperAdmin() || $user->company_id === null) {
            return $next($request);
        }

        $company = Company::query()->find($user->company_id);
        $status = $company?->status ?? Company::STATUS_INACTIVE;

        if ($status === Company::STATUS_ACTIVE) {
            if ($request->routeIs('tenant.access-status')) {
                return redirect()->route('admin.dashboard');
            }

            return $next($request);
        }

        if ($request->routeIs('tenant.access-status')) {
            return $next($request);
        }

        return redirect()->route('tenant.access-status');
    }
}