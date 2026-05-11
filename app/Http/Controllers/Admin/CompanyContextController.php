<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompanyContextController extends Controller
{
    public function switch(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $companyId = $request->integer('company_id');

        if ($companyId === 0) {
            $request->session()->forget('selected_company_id');

            return back()->with('success', 'Now viewing all companies.');
        }

        $company = Company::query()->findOrFail($companyId);

        $request->session()->put('selected_company_id', $company->id);

        return back()->with('success', 'Switched to '.$company->name.'.');
    }
}
