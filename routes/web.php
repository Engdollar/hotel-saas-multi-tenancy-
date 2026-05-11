<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\CompanyProfileController;
use App\Http\Controllers\Admin\CompanyContextController;
use App\Http\Controllers\Admin\DashboardIntelligenceController;
use App\Http\Controllers\Admin\DashboardPreferenceController;
use App\Http\Controllers\Admin\DataExportController;
use App\Http\Controllers\Admin\ActivityReportController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SearchController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SupportTicketController;
use App\Http\Controllers\Admin\SupportTicketReplyController;
use App\Http\Controllers\Admin\TenantWorkspaceController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\ProfileController;
use App\Services\InstallerService;
use App\Http\Controllers\TenantAccessStatusController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! app(InstallerService::class)->isInstalled()) {
        return redirect()->route('install.create');
    }

    if (! auth()->check()) {
        return redirect()->route('login');
    }

    return auth()->user()->can('read-dashboard')
        ? redirect()->route('admin.dashboard')
        : redirect()->route('profile.edit');
})->middleware(['set-company-context', 'tenant-company-active']);

Route::get('/install', [InstallController::class, 'create'])->name('install.create');
Route::post('/install/test-database', [InstallController::class, 'testDatabase'])->name('install.test-database');
Route::post('/install', [InstallController::class, 'store'])->name('install.store');
Route::get('/documentation', [DocumentationController::class, 'index'])->name('documentation.index');

Route::get('/dashboard', function () {
    return auth()->user()->can('read-dashboard')
        ? redirect()->route('admin.dashboard')
        : redirect()->route('profile.edit');
})->middleware(['auth', 'verified', 'set-company-context', 'tenant-company-active'])->name('dashboard');

Route::middleware(['auth', 'verified', 'set-company-context', 'tenant-company-active', 'no-back-history'])->group(function () {
    Route::get('/tenant/access-status', [TenantAccessStatusController::class, 'show'])->name('tenant.access-status');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('admin')->as('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->middleware('permission:read-dashboard')
            ->name('dashboard');
        Route::get('/workspace/{module}', [TenantWorkspaceController::class, 'show'])
            ->middleware('permission:read-dashboard')
            ->name('workspace.modules.show');
        Route::get('/workspace/{module}/records/{record}', [TenantWorkspaceController::class, 'showRecord'])
            ->middleware('permission:read-dashboard')
            ->name('workspace.records.show');
        Route::get('/workspace/{module}/records/{record}/edit', [TenantWorkspaceController::class, 'edit'])
            ->middleware('permission:read-dashboard')
            ->name('workspace.records.edit');
        Route::get('/workspace/{module}/create', [TenantWorkspaceController::class, 'create'])
            ->middleware('permission:read-dashboard')
            ->name('workspace.modules.create');
        Route::post('/workspace/{module}', [TenantWorkspaceController::class, 'store'])
            ->middleware('permission:read-dashboard')
            ->name('workspace.modules.store');
        Route::post('/workspace/{module}/bulk-actions', [TenantWorkspaceController::class, 'storeBulkAction'])
            ->middleware('permission:read-dashboard')
            ->name('workspace.modules.bulk-actions.store');
        Route::put('/workspace/{module}/records/{record}', [TenantWorkspaceController::class, 'update'])
            ->middleware('permission:read-dashboard')
            ->name('workspace.records.update');
        Route::post('/workspace/{module}/records/{record}/actions/{action}', [TenantWorkspaceController::class, 'storeAction'])
            ->middleware('permission:read-dashboard')
            ->name('workspace.records.actions.store');
        Route::put('/dashboard/widgets', [DashboardPreferenceController::class, 'update'])
            ->middleware('permission:read-dashboard')
            ->name('dashboard.widgets');
        Route::get('/intelligence', [DashboardIntelligenceController::class, 'index'])
            ->middleware('role:Super Admin')
            ->name('intelligence.index');

        Route::get('/activity', [ActivityReportController::class, 'index'])
            ->middleware('role:Super Admin')
            ->name('activity.index');

        Route::get('/reports', [ReportsController::class, 'index'])
            ->middleware('role:Super Admin')
            ->name('reports.index');
        Route::get('/reports/export/{format}', [ReportsController::class, 'export'])
            ->middleware('role:Super Admin')
            ->whereIn('format', ['xlsx', 'pdf'])
            ->name('reports.export');

        Route::get('/search', [SearchController::class, 'index'])
            ->name('search.index');

        Route::get('/tickets', [SupportTicketController::class, 'index'])
            ->middleware('permission:read-ticket')
            ->name('tickets.index');
        Route::get('/tickets/create', [SupportTicketController::class, 'create'])
            ->middleware('permission:create-ticket')
            ->name('tickets.create');
        Route::post('/tickets', [SupportTicketController::class, 'store'])
            ->middleware('permission:create-ticket')
            ->name('tickets.store');
        Route::get('/tickets/{ticket}', [SupportTicketController::class, 'show'])
            ->middleware('permission:read-ticket')
            ->name('tickets.show');
        Route::get('/tickets/{ticket}/stream', [SupportTicketController::class, 'stream'])
            ->middleware('permission:read-ticket')
            ->name('tickets.stream');
        Route::put('/tickets/{ticket}', [SupportTicketController::class, 'update'])
            ->middleware('permission:update-ticket')
            ->name('tickets.update');
        Route::post('/tickets/{ticket}/replies', [SupportTicketReplyController::class, 'store'])
            ->middleware('permission:update-ticket')
            ->name('tickets.replies.store');
        Route::post('/tickets/editor/upload-image', [SupportTicketReplyController::class, 'uploadEditorImage'])
            ->middleware('permission:create-ticket|update-ticket')
            ->name('tickets.editor.upload-image');

        Route::get('/notifications', [NotificationController::class, 'index'])
            ->middleware('role:Super Admin')
            ->name('notifications.index');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
            ->middleware('role:Super Admin')
            ->name('notifications.read');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
            ->middleware('role:Super Admin')
            ->name('notifications.read-all');

        Route::get('/users/data', [UserController::class, 'data'])
            ->middleware('permission:read-user')
            ->name('users.data');
        Route::get('/users', [UserController::class, 'index'])
            ->middleware('permission:read-user')
            ->name('users.index');
        Route::get('/users/export/{format}', [DataExportController::class, 'users'])
            ->middleware('permission:read-user')
            ->whereIn('format', ['csv', 'xlsx', 'pdf'])
            ->name('users.export');
        Route::get('/users/create', [UserController::class, 'create'])
            ->middleware('permission:create-user')
            ->name('users.create');
        Route::post('/users', [UserController::class, 'store'])
            ->middleware('permission:create-user')
            ->name('users.store');
        Route::get('/users/{user}', [UserController::class, 'show'])
            ->middleware('permission:show-user')
            ->name('users.show');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])
            ->middleware('permission:edit-user')
            ->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])
            ->middleware('permission:update-user')
            ->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])
            ->middleware('permission:delete-user')
            ->name('users.destroy');

        Route::get('/roles/data', [RoleController::class, 'data'])
            ->middleware('permission:read-role')
            ->name('roles.data');
        Route::get('/roles', [RoleController::class, 'index'])
            ->middleware('permission:read-role')
            ->name('roles.index');
        Route::get('/roles/export/{format}', [DataExportController::class, 'roles'])
            ->middleware('permission:read-role')
            ->whereIn('format', ['csv', 'xlsx', 'pdf'])
            ->name('roles.export');
        Route::get('/roles/create', [RoleController::class, 'create'])
            ->middleware('permission:create-role')
            ->name('roles.create');
        Route::post('/roles', [RoleController::class, 'store'])
            ->middleware('permission:create-role')
            ->name('roles.store');
        Route::get('/roles/{role}', [RoleController::class, 'show'])
            ->middleware('permission:show-role')
            ->name('roles.show');
        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])
            ->middleware('permission:edit-role')
            ->name('roles.edit');
        Route::put('/roles/{role}', [RoleController::class, 'update'])
            ->middleware('permission:update-role')
            ->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
            ->middleware('permission:delete-role')
            ->name('roles.destroy');

        Route::get('/permissions/data', [PermissionController::class, 'data'])
            ->middleware('permission:read-permission')
            ->name('permissions.data');
        Route::get('/permissions', [PermissionController::class, 'index'])
            ->middleware('permission:read-permission')
            ->name('permissions.index');
        Route::get('/permissions/export/{format}', [DataExportController::class, 'permissions'])
            ->middleware('permission:read-permission')
            ->whereIn('format', ['csv', 'xlsx', 'pdf'])
            ->name('permissions.export');
        Route::get('/permissions/create', [PermissionController::class, 'create'])
            ->middleware('permission:create-permission')
            ->name('permissions.create');
        Route::post('/permissions', [PermissionController::class, 'store'])
            ->middleware('permission:create-permission')
            ->name('permissions.store');
        Route::get('/permissions/{permission}', [PermissionController::class, 'show'])
            ->middleware('permission:show-permission')
            ->name('permissions.show');
        Route::get('/permissions/{permission}/edit', [PermissionController::class, 'edit'])
            ->middleware('permission:edit-permission')
            ->name('permissions.edit');
        Route::put('/permissions/{permission}', [PermissionController::class, 'update'])
            ->middleware('permission:update-permission')
            ->name('permissions.update');
        Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy'])
            ->middleware('permission:delete-permission')
            ->name('permissions.destroy');

        Route::middleware('permission:read-setting')->group(function () {
            Route::get('/company-profile', [CompanyProfileController::class, 'edit'])->name('company-profile.edit');
            Route::put('/company-profile', [CompanyProfileController::class, 'update'])->name('company-profile.update');

            Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
            Route::get('/settings/dashboard-documentation', [SettingsController::class, 'dashboardDocumentation'])->name('settings.dashboard-documentation');
            Route::get('/settings/template-documentation', [SettingsController::class, 'templateDocumentation'])->name('settings.template-documentation');
        });

        Route::middleware('permission:update-setting')->group(function () {
            Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
            Route::put('/settings/dashboard-studio', [SettingsController::class, 'updateDashboardStudio'])->name('settings.dashboard-studio.update');
            Route::put('/settings/document-templates', [SettingsController::class, 'updateDocumentTemplates'])->name('settings.document-templates.update');
            Route::put('/settings/email-templates', [SettingsController::class, 'updateEmailTemplates'])->name('settings.email-templates.update');
            Route::post('/settings/theme-presets/preview', [SettingsController::class, 'previewThemePresets'])->name('settings.theme-presets.preview');
            Route::post('/settings/theme-presets', [SettingsController::class, 'storeGeneratedThemePresets'])->name('settings.theme-presets.store');
            Route::post('/settings/theme-presets/{themePreset}/duplicate', [SettingsController::class, 'duplicateThemePreset'])->name('settings.theme-presets.duplicate');
            Route::delete('/settings/theme-presets/{themePreset}', [SettingsController::class, 'destroyThemePreset'])->name('settings.theme-presets.destroy');
        });

        Route::middleware('role:Super Admin')->group(function () {
            Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
            Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
            Route::post('/companies/bulk-lifecycle', [CompanyController::class, 'bulkLifecycleAction'])->name('companies.bulk-lifecycle');
            Route::put('/companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
            Route::delete('/companies/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy');
            Route::post('/companies/{company}/approve', [CompanyController::class, 'approve'])->name('companies.approve');
            Route::post('/companies/{company}/activate', [CompanyController::class, 'activate'])->name('companies.activate');
            Route::post('/companies/{company}/suspend', [CompanyController::class, 'suspend'])->name('companies.suspend');
            Route::post('/companies/{company}/mark-pending', [CompanyController::class, 'markPending'])->name('companies.mark-pending');
            Route::post('/companies/switch', [CompanyContextController::class, 'switch'])->name('companies.switch');

            Route::post('/settings/generate', [SettingsController::class, 'generate'])->name('settings.generate');
        });
    });
});

require __DIR__.'/auth.php';
