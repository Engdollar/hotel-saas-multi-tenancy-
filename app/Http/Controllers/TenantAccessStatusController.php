<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantAccessStatusController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user || $user->isSuperAdmin() || $user->company_id === null) {
            return redirect()->route('dashboard');
        }

        $company = Company::query()->find($user->company_id);
        $status = $company?->status ?? Company::STATUS_INACTIVE;

        if ($status === Company::STATUS_ACTIVE) {
            return redirect()->route('admin.dashboard');
        }

        $statusContent = match ($status) {
            Company::STATUS_PENDING => [
                'title' => 'Workspace pending approval',
                'message' => 'Your company was created successfully. Access is currently paused until a Super Admin approves this tenancy.',
                'pill' => 'Pending approval',
            ],
            default => [
                'title' => 'Workspace inactive',
                'message' => 'Your tenancy is currently inactive. Please contact the platform administrator to reactivate access.',
                'pill' => 'Inactive workspace',
            ],
        };

        return view('tenant.access-status', [
            'company' => $company,
            'status' => $status,
            'statusContent' => $statusContent,
        ]);
    }
}