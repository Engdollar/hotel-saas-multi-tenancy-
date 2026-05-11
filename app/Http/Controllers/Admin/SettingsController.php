<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateDashboardStudioRequest;
use App\Http\Requests\UpdateDocumentTemplatesRequest;
use App\Http\Requests\UpdateEmailTemplatesRequest;
use App\Http\Requests\GenerateThemePresetPreviewRequest;
use App\Http\Requests\StoreGeneratedThemePresetRequest;
use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Setting;
use App\Models\ThemePreset;
use App\Services\AdminNotificationService;
use App\Services\DashboardService;
use App\Services\PermissionGeneratorService;
use App\Services\SettingsService;
use App\Services\TenancyDomainService;
use App\Services\ThemePresetGeneratorService;
use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Illuminate\View\View;
use InvalidArgumentException;

class SettingsController extends Controller
{
    public function __construct(
        protected SettingsService $settingsService,
        protected DashboardService $dashboardService,
        protected PermissionGeneratorService $permissionGeneratorService,
        protected ThemePresetGeneratorService $themePresetGeneratorService,
        protected AdminNotificationService $notificationService,
        protected TenancyDomainService $tenancyDomainService,
        protected CurrentCompanyContext $companyContext,
    ) {
    }

    public function index(): View
    {
        $this->authorize('viewAny', Setting::class);

        $dashboard = $this->dashboardService->buildDashboard(auth()->user());
        $dashboardStudio = $this->dashboardService->dashboardStudio();

        return view('admin.settings.index', [
            'settings' => $this->settingsService->all(),
            'themePresets' => $this->settingsService->themePresets(),
            'customThemePresets' => $this->settingsService->customPresetLibrary(),
            'themeModes' => $this->settingsService->themeModes(),
            'pdfHeaderTemplates' => $this->settingsService->templatePresets(SettingsService::PDF_HEADER_TEMPLATE_TYPE),
            'excelHeaderTemplates' => $this->settingsService->templatePresets(SettingsService::EXCEL_HEADER_TEMPLATE_TYPE),
            'emailTemplates' => $this->settingsService->templatePresets(SettingsService::EMAIL_TEMPLATE_TYPE),
            'selectedPdfHeaderTemplate' => $this->settingsService->selectedTemplate(SettingsService::PDF_HEADER_TEMPLATE_TYPE),
            'selectedExcelHeaderTemplate' => $this->settingsService->selectedTemplate(SettingsService::EXCEL_HEADER_TEMPLATE_TYPE),
            'selectedEmailTemplate' => $this->settingsService->selectedTemplate(SettingsService::EMAIL_TEMPLATE_TYPE, [
                'recipient_name' => auth()->user()->name,
                'action_url' => route('admin.dashboard'),
            ]),
            'generatedThemePresets' => session('generatedThemePresets', []),
            'keywordPacks' => $this->themePresetGeneratorService->keywordPacks(),
            'permissionGroups' => $this->permissionGeneratorService->groupedPermissions(),
            'widgetState' => $dashboard['widgetState'],
            'widgetLayout' => $dashboard['widgetLayout'],
            'dragEnabled' => $dashboard['dragEnabled'],
            'canDragWidgets' => $dashboard['canDragWidgets'],
            'dashboardWidgetOptions' => $dashboard['widgetDefinitions'],
            'dashboardStudio' => $dashboardStudio,
            'dashboardStatSources' => $this->dashboardService->statSources(),
            'dashboardChartSources' => $this->dashboardService->chartSources(),
            'dashboardIconOptions' => $this->dashboardService->iconOptions(),
            'dashboardChartTypeOptions' => $this->dashboardService->chartTypeOptions(),
            'tenancyBaseDomain' => $this->tenancyDomainService->baseDomain(),
        ]);
    }

    public function dashboardDocumentation(): View
    {
        $this->authorize('viewAny', Setting::class);

        return view('admin.settings.dashboard-documentation', [
            'statSources' => $this->dashboardService->statSources(),
            'chartSources' => $this->dashboardService->chartSources(),
            'iconOptions' => $this->dashboardService->iconOptions(),
            'chartTypes' => $this->dashboardService->chartTypeOptions(),
        ]);
    }

    public function templateDocumentation(): View
    {
        $this->authorize('viewAny', Setting::class);

        return view('admin.settings.template-documentation', [
            'templateVariables' => [
                ['token' => '{{ project_title }}', 'description' => 'The current project or workspace title from settings.'],
                ['token' => '{{ export_title }}', 'description' => 'The title used for the current PDF or Excel export.'],
                ['token' => '{{ export_subtitle }}', 'description' => 'The subtitle or context line used for the current export.'],
                ['token' => '{{ export_generated_at }}', 'description' => 'The formatted date and time when the export is generated.'],
                ['token' => '{{ recipient_name }}', 'description' => 'The recipient name used in email templates.'],
                ['token' => '{{ action_url }}', 'description' => 'The call-to-action link used in email templates.'],
                ['token' => '{{ support_email }}', 'description' => 'The support email address from your mail configuration or provided context.'],
            ],
        ]);
    }

    public function update(UpdateSettingsRequest $request): JsonResponse|RedirectResponse
    {
        $this->authorize('manage', Setting::class);

        $this->settingsService->update([
            'project_title' => $request->validated('project_title'),
            'logo' => $request->file('logo'),
            'favicon' => $request->file('favicon'),
            'theme_preset' => $request->validated('theme_preset'),
            'theme_mode' => $request->validated('theme_mode'),
        ]);

        if (auth()->user()?->isSuperAdmin()) {
            Setting::withoutGlobalScopes()->updateOrCreate(
                ['company_id' => null, 'key' => 'tenancy_base_domain'],
                ['value' => Str::lower(trim((string) $request->validated('tenancy_base_domain')))],
            );
        }

        $this->notificationService->send('Settings updated', 'Branding and theme settings were updated.', route('admin.settings.index'));

        if ($request->expectsJson()) {
            return response()->json([
                'saved' => true,
                'message' => 'Settings updated successfully.',
            ]);
        }

        return redirect()->route('admin.settings.index')->with('success', 'Settings updated successfully.');
    }

    public function updateDashboardStudio(UpdateDashboardStudioRequest $request): JsonResponse|RedirectResponse
    {
        $this->authorize('manage', Setting::class);

        $validated = $request->validated();

        $this->settingsService->update([
            'dashboard_stats' => json_decode($validated['dashboard_stats'], true) ?: [],
            'dashboard_charts' => json_decode($validated['dashboard_charts'], true) ?: [],
            'auth_login_visual_mode' => $validated['auth_login_visual_mode'],
            'auth_register_visual_mode' => $validated['auth_register_visual_mode'],
            'auth_login_visual_image' => $request->file('auth_login_visual_image'),
            'auth_register_visual_image' => $request->file('auth_register_visual_image'),
        ]);

        $this->notificationService->send('Dashboard studio updated', 'Dashboard metrics, charts, and auth media were refreshed.', route('admin.settings.index'));

        if ($request->expectsJson()) {
            return response()->json([
                'saved' => true,
                'message' => 'Dashboard studio updated successfully.',
            ]);
        }

        return redirect()->route('admin.settings.index')->with('success', 'Dashboard studio updated successfully.');
    }

    public function updateDocumentTemplates(UpdateDocumentTemplatesRequest $request): JsonResponse|RedirectResponse
    {
        $this->authorize('manage', Setting::class);

        $validated = $request->validated();
        $scope = $validated['template_scope'];

        if ($scope === 'pdf') {
            $this->settingsService->update([
                'pdf_header_template' => $validated['pdf_header_template'],
            ]);

            $this->settingsService->saveTemplateOverride(SettingsService::PDF_HEADER_TEMPLATE_TYPE, $validated['pdf_header_template'], [
                'name' => $this->settingsService->templatePresets(SettingsService::PDF_HEADER_TEMPLATE_TYPE)[$validated['pdf_header_template']]['name'] ?? 'PDF Header Template',
                'description' => $this->settingsService->templatePresets(SettingsService::PDF_HEADER_TEMPLATE_TYPE)[$validated['pdf_header_template']]['description'] ?? null,
                'content' => [
                    'kicker' => $validated['pdf_header_kicker'],
                    'title' => $validated['pdf_header_title'],
                    'subtitle' => $validated['pdf_header_subtitle'] ?? '',
                    'accent_start' => $validated['pdf_header_accent_start'],
                    'accent_end' => $validated['pdf_header_accent_end'],
                    'surface' => $validated['pdf_header_surface'],
                    'border' => $validated['pdf_header_border'],
                    'text_primary' => $validated['pdf_header_text_primary'],
                    'text_muted' => $validated['pdf_header_text_muted'],
                    'heading_background' => $validated['pdf_header_heading_background'],
                    'heading_text' => $validated['pdf_header_heading_text'],
                ],
            ]);

            $this->notificationService->send('PDF template updated', 'The PDF export header template was refreshed.', route('admin.settings.index'));

            if ($request->expectsJson()) {
                return response()->json([
                    'saved' => true,
                    'message' => 'PDF template updated successfully.',
                ]);
            }

            return redirect()->route('admin.settings.index')->with('success', 'PDF template updated successfully.');
        }

        $this->settingsService->update([
            'excel_header_template' => $validated['excel_header_template'],
        ]);

        $this->settingsService->saveTemplateOverride(SettingsService::EXCEL_HEADER_TEMPLATE_TYPE, $validated['excel_header_template'], [
            'name' => $this->settingsService->templatePresets(SettingsService::EXCEL_HEADER_TEMPLATE_TYPE)[$validated['excel_header_template']]['name'] ?? 'Excel Header Template',
            'description' => $this->settingsService->templatePresets(SettingsService::EXCEL_HEADER_TEMPLATE_TYPE)[$validated['excel_header_template']]['description'] ?? null,
            'content' => [
                'title' => $validated['excel_header_title'],
                'subtitle' => $validated['excel_header_subtitle'] ?? '',
                'accent' => $validated['excel_header_accent'],
                'title_text' => $validated['excel_header_title_text'],
                'meta_background' => $validated['excel_header_meta_background'],
                'meta_text' => $validated['excel_header_meta_text'],
                'heading_background' => $validated['excel_header_heading_background'],
                'heading_text' => $validated['excel_header_heading_text'],
                'body_border' => $validated['excel_header_body_border'],
            ],
        ]);

        $this->notificationService->send('Excel template updated', 'The Excel export header template was refreshed.', route('admin.settings.index'));

        if ($request->expectsJson()) {
            return response()->json([
                'saved' => true,
                'message' => 'Excel template updated successfully.',
            ]);
        }

        return redirect()->route('admin.settings.index')->with('success', 'Excel template updated successfully.');
    }

    public function updateEmailTemplates(UpdateEmailTemplatesRequest $request): JsonResponse|RedirectResponse
    {
        $this->authorize('manage', Setting::class);

        $validated = $request->validated();

        $this->settingsService->update([
            'email_template' => $validated['email_template'],
        ]);

        $this->settingsService->saveTemplateOverride(SettingsService::EMAIL_TEMPLATE_TYPE, $validated['email_template'], [
            'name' => $this->settingsService->templatePresets(SettingsService::EMAIL_TEMPLATE_TYPE)[$validated['email_template']]['name'] ?? 'Email Template',
            'description' => $this->settingsService->templatePresets(SettingsService::EMAIL_TEMPLATE_TYPE)[$validated['email_template']]['description'] ?? null,
            'content' => [
                'subject' => $validated['email_subject'],
                'headline' => $validated['email_headline'],
                'greeting' => $validated['email_greeting'],
                'body_html' => $validated['email_body_html'],
                'button_label' => $validated['email_button_label'],
                'signature' => $validated['email_signature'],
                'accent' => $validated['email_accent'],
                'surface' => $validated['email_surface'],
            ],
        ]);

        $this->notificationService->send('Email template updated', 'The selected email template preset was saved.', route('admin.settings.index'));

        if ($request->expectsJson()) {
            return response()->json([
                'saved' => true,
                'message' => 'Email template updated successfully.',
            ]);
        }

        return redirect()->route('admin.settings.index')->with('success', 'Email template updated successfully.');
    }

    public function generate(): RedirectResponse
    {
        Artisan::call('system:setup');

        $this->notificationService->send('RBAC generator executed', 'Roles and permissions were regenerated.', route('admin.settings.index'));

        return redirect()->route('admin.settings.index')->with('success', trim(Artisan::output()) ?: 'Roles and permissions generated successfully.');
    }

    public function previewThemePresets(GenerateThemePresetPreviewRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $keywords = $this->parseKeywords((string) ($validated['keywords'] ?? ''));
        $packs = $validated['packs'] ?? [];
        $candidates = $this->themePresetGeneratorService->generate($keywords, (int) $validated['count'], $packs);

        return redirect()
            ->route('admin.settings.index')
            ->withInput()
            ->with('generatedThemePresets', $candidates)
            ->with('success', count($candidates).' preset candidates generated.');
    }

    public function storeGeneratedThemePresets(StoreGeneratedThemePresetRequest $request): RedirectResponse
    {
        $candidates = json_decode(base64_decode($request->validated('generated_presets'), true) ?: '', true);

        if (! is_array($candidates)) {
            return redirect()->route('admin.settings.index')->with('error', 'Unable to decode the generated presets payload.');
        }

        $selectedSlugs = collect($request->validated('selected_presets'));
        $replace = $request->boolean('replace_existing');
        $saved = 0;

        foreach ($selectedSlugs as $slug) {
            $candidate = collect($candidates)->firstWhere('slug', $slug);

            if (! is_array($candidate)) {
                continue;
            }

            try {
                $this->settingsService->saveThemePreset([
                    'slug' => $candidate['slug'],
                    'name' => $candidate['name'],
                    'description' => $candidate['description'] ?? null,
                    'keywords' => $candidate['keywords'] ?? [],
                    'swatches' => $candidate['swatches'] ?? [],
                    'light_tokens' => $candidate['light_tokens'] ?? [],
                    'dark_tokens' => $candidate['dark_tokens'] ?? [],
                    'is_generated' => true,
                ], $replace);
            } catch (InvalidArgumentException $exception) {
                return redirect()->route('admin.settings.index')->with('error', $exception->getMessage());
            }

            $saved++;
        }

        $this->notificationService->send('Theme presets saved', $saved.' generated theme presets were saved to the library.', route('admin.settings.index'));

        return redirect()->route('admin.settings.index')->with('success', $saved.' generated presets saved successfully.');
    }

    public function duplicateThemePreset(ThemePreset $themePreset): RedirectResponse
    {
        $duplicate = $this->settingsService->duplicateThemePreset($themePreset);

        $this->notificationService->send('Theme preset duplicated', $duplicate->name.' was added to the custom library.', route('admin.settings.index'));

        return redirect()->route('admin.settings.index')->with('success', 'Theme preset duplicated successfully.');
    }

    public function destroyThemePreset(ThemePreset $themePreset): RedirectResponse
    {
        $this->settingsService->deleteThemePreset($themePreset);

        $this->notificationService->send('Theme preset deleted', 'The selected custom preset was removed from the library.', route('admin.settings.index'));

        return redirect()->route('admin.settings.index')->with('success', 'Theme preset deleted successfully.');
    }

    protected function parseKeywords(string $rawKeywords): array
    {
        return collect(preg_split('/[\r\n,]+/', $rawKeywords) ?: [])
            ->map(fn (string $keyword) => Str::of($keyword)->trim()->value())
            ->filter()
            ->values()
            ->all();
    }
}