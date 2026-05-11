<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\TenancyDomainService;
use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompanyProfileController extends Controller
{
    public function __construct(
        protected CurrentCompanyContext $companyContext,
        protected TenancyDomainService $tenancyDomainService,
    )
    {
    }

    public function edit(): View
    {
        $company = $this->currentCompany();

        return view('admin.company-profile.edit', [
            'company' => $company,
            'tenancyBaseDomain' => $this->tenancyDomainService->baseDomain(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $company = $this->currentCompany();

        $data = $request->all();
        $data['domain'] = $this->tenancyDomainService->qualifyDomain($request->input('domain'));

        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255', Rule::unique('companies', 'domain')->ignore($company)],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ])->validate();

        unset($validated['status']);
        $company->update($validated);

        return back()->with('success', 'Company profile updated successfully.');
    }

    protected function currentCompany(): Company
    {
        abort_unless($this->companyContext->id() !== null, 403);

        return Company::query()->findOrFail($this->companyContext->id());
    }
}
