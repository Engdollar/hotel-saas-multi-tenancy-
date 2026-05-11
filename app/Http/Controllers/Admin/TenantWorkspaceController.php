<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TenantWorkspaceRecordService;
use App\Services\TenantWorkspaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantWorkspaceController extends Controller
{
    public function __construct(protected TenantWorkspaceService $tenantWorkspaceService)
    {
    }

    public function showRecord(Request $request, string $module, string $record): View
    {
        $page = app(TenantWorkspaceRecordService::class)->recordPage($request->user(), $module, $record);

        return view('admin.workspace.show', [
            ...$page,
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => $page['module']['label'], 'url' => $page['module']['route']],
                ['label' => $page['title']],
            ],
        ]);
    }

    public function show(Request $request, string $module): View
    {
        $page = $this->tenantWorkspaceService->modulePage($request->user(), $module, $request);

        return view('admin.workspace.index', [
            ...$page,
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => $page['module']['label']],
            ],
        ]);
    }

    public function create(Request $request, string $module): View
    {
        $form = $this->tenantWorkspaceService->moduleCreateForm($request->user(), $module);

        return view('admin.workspace.create', [
            ...$form,
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => $form['module']['label'], 'url' => $form['module']['route']],
                ['label' => 'Create'],
            ],
        ]);
    }

    public function edit(Request $request, string $module, string $record): View
    {
        $form = $this->tenantWorkspaceService->moduleEditForm($request->user(), $module, $record);

        return view('admin.workspace.create', [
            ...$form,
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => $form['module']['label'], 'url' => $form['module']['route']],
                ['label' => $form['record_title'], 'url' => route('admin.workspace.records.show', ['module' => $module, 'record' => $record])],
                ['label' => 'Edit'],
            ],
        ]);
    }

    public function store(Request $request, string $module): RedirectResponse
    {
        $result = $this->tenantWorkspaceService->storeModule($request->user(), $module, $request);

        return redirect($result['redirect'])->with('success', $result['message']);
    }

    public function update(Request $request, string $module, string $record): RedirectResponse
    {
        $result = $this->tenantWorkspaceService->updateModule($request->user(), $module, $record, $request);

        return redirect($result['redirect'])->with('success', $result['message']);
    }

    public function storeAction(Request $request, string $module, string $record, string $action): RedirectResponse
    {
        $result = app(TenantWorkspaceRecordService::class)->performAction($request->user(), $module, $record, $action, $request);

        return redirect($result['redirect'])->with('success', $result['message']);
    }

    public function storeBulkAction(Request $request, string $module): RedirectResponse
    {
        $result = $this->tenantWorkspaceService->performBulkAction($request->user(), $module, $request);

        return redirect($result['redirect'])->with('success', $result['message']);
    }
}