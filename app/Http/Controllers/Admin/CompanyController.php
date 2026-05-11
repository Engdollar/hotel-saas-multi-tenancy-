<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\TenancyDomainService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function __construct(protected TenancyDomainService $tenancyDomainService)
    {
    }

    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $status = (string) $request->string('status', 'all');
        $query = trim((string) $request->string('query', ''));

        if (! in_array($status, ['all', Company::STATUS_PENDING, Company::STATUS_ACTIVE, Company::STATUS_INACTIVE], true)) {
            $status = 'all';
        }

        $companiesQuery = Company::query()
            ->withCount('users')
            ->when($status !== 'all', fn ($builder) => $builder->where('status', $status))
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where(function ($nested) use ($query) {
                    $nested
                        ->where('name', 'like', "%{$query}%")
                        ->orWhere('domain', 'like', "%{$query}%");
                });
            })
            ->latest();

        return view('admin.companies.index', [
            'companies' => $companiesQuery->paginate(15)->withQueryString(),
            'stats' => [
                'total' => Company::query()->count(),
                'pending' => Company::query()->where('status', Company::STATUS_PENDING)->count(),
                'active' => Company::query()->where('status', Company::STATUS_ACTIVE)->count(),
                'inactive' => Company::query()->where('status', Company::STATUS_INACTIVE)->count(),
            ],
            'filters' => [
                'status' => $status,
                'query' => $query,
            ],
            'tenancyBaseDomain' => $this->tenancyDomainService->baseDomain(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $data = $request->all();
        $data['domain'] = $this->tenancyDomainService->qualifyDomain($request->input('domain'));

        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255', Rule::unique('companies', 'domain')],
            'status' => ['required', Rule::in([Company::STATUS_PENDING, Company::STATUS_ACTIVE, Company::STATUS_INACTIVE])],
        ])->validate();

        Company::query()->create($validated);

        return redirect()->route('admin.companies.index')->with('success', 'Company created successfully.');
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $previousStatus = $company->status;

        $data = $request->all();
        $data['domain'] = $this->tenancyDomainService->qualifyDomain($request->input('domain'));

        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255', Rule::unique('companies', 'domain')->ignore($company)],
            'status' => ['required', Rule::in([Company::STATUS_PENDING, Company::STATUS_ACTIVE, Company::STATUS_INACTIVE])],
        ])->validate();

        $company->update($validated);

        if ($previousStatus !== $company->status) {
            $this->logStatusChange($company, $previousStatus, $company->status, 'manual-update');
        }

        return redirect()->route('admin.companies.index')->with('success', 'Company updated successfully.');
    }

    public function approve(Company $company): RedirectResponse
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        if (! $this->transitionCompanyStatus($company, Company::STATUS_ACTIVE, [Company::STATUS_PENDING], 'approve')) {
            return redirect()->route('admin.companies.index')->with('error', 'Only pending companies can be approved.');
        }

        return redirect()->route('admin.companies.index')->with('success', 'Company approved and activated successfully.');
    }

    public function activate(Company $company): RedirectResponse
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        if (! $this->transitionCompanyStatus($company, Company::STATUS_ACTIVE, [Company::STATUS_PENDING, Company::STATUS_INACTIVE], 'activate')) {
            return redirect()->route('admin.companies.index')->with('error', 'Company is already active.');
        }

        return redirect()->route('admin.companies.index')->with('success', 'Company activated successfully.');
    }

    public function suspend(Company $company): RedirectResponse
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        if (! $this->transitionCompanyStatus($company, Company::STATUS_INACTIVE, [Company::STATUS_PENDING, Company::STATUS_ACTIVE], 'suspend')) {
            return redirect()->route('admin.companies.index')->with('error', 'Company is already inactive.');
        }

        return redirect()->route('admin.companies.index')->with('success', 'Company has been suspended.');
    }

    public function markPending(Company $company): RedirectResponse
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        if (! $this->transitionCompanyStatus($company, Company::STATUS_PENDING, [Company::STATUS_ACTIVE, Company::STATUS_INACTIVE], 'mark-pending')) {
            return redirect()->route('admin.companies.index')->with('error', 'Company is already pending review.');
        }

        return redirect()->route('admin.companies.index')->with('success', 'Company moved to pending review.');
    }

    public function bulkLifecycleAction(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'action' => ['required', Rule::in(['approve', 'suspend', 'activate'])],
            'companies' => ['required', 'array', 'min:1'],
            'companies.*' => ['integer', 'exists:companies,id'],
        ]);

        $companies = Company::query()
            ->whereIn('id', $validated['companies'])
            ->get();

        $changed = $companies->reduce(function (int $count, Company $company) use ($validated) {
            $didChange = match ($validated['action']) {
                'approve' => $this->transitionCompanyStatus($company, Company::STATUS_ACTIVE, [Company::STATUS_PENDING], 'bulk-approve'),
                'suspend' => $this->transitionCompanyStatus($company, Company::STATUS_INACTIVE, [Company::STATUS_PENDING, Company::STATUS_ACTIVE], 'bulk-suspend'),
                'activate' => $this->transitionCompanyStatus($company, Company::STATUS_ACTIVE, [Company::STATUS_PENDING, Company::STATUS_INACTIVE], 'bulk-activate'),
                default => false,
            };

            return $didChange ? $count + 1 : $count;
        }, 0);

        if ($changed === 0) {
            return redirect()->route('admin.companies.index')->with('error', 'No selected companies matched the chosen lifecycle action.');
        }

        return redirect()->route('admin.companies.index')->with('success', "Bulk action applied to {$changed} compan".($changed === 1 ? 'y.' : 'ies.'));
    }

    public function destroy(Request $request, Company $company): RedirectResponse
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        DB::transaction(function () use ($company) {
            User::withoutGlobalScopes()->where('company_id', $company->id)->delete();
            Role::withoutGlobalScopes()->where('company_id', $company->id)->delete();
            Permission::withoutGlobalScopes()->where('company_id', $company->id)->delete();
            $company->delete();
        });

        if ((int) $request->session()->get('selected_company_id') === $company->id) {
            $request->session()->forget('selected_company_id');
        }

        return redirect()->route('admin.companies.index')->with('success', 'Company and tenant data deleted successfully.');
    }

    protected function transitionCompanyStatus(Company $company, string $toStatus, array $allowedFrom, string $reason): bool
    {
        if (! in_array($company->status, $allowedFrom, true) || $company->status === $toStatus) {
            return false;
        }

        $fromStatus = $company->status;
        $company->update(['status' => $toStatus]);
        $this->logStatusChange($company, $fromStatus, $toStatus, $reason);

        return true;
    }

    protected function logStatusChange(Company $company, string $fromStatus, string $toStatus, string $reason): void
    {
        activity('company-lifecycle')
            ->causedBy(auth()->user())
            ->performedOn($company)
            ->event('company-status-changed')
            ->withProperties([
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'reason' => $reason,
            ])
            ->log("Company status changed from {$fromStatus} to {$toStatus}");
    }
}
