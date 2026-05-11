<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AdminCollectionExport;
use App\Http\Controllers\Controller;
use App\Services\ReportsService;
use App\Services\SettingsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class ReportsController extends Controller
{
    public function __construct(
        protected ReportsService $reportsService,
        protected SettingsService $settingsService,
    )
    {
    }

    public function index(Request $request)
    {
        $filters = $this->reportsService->filtersFromRequest($request);
        $query = $this->reportsService->activityQuery($filters);

        return view('admin.reports.index', [
            'filters' => $filters,
            'summary' => $this->reportsService->summary($filters),
            'moduleBreakdown' => $this->reportsService->moduleBreakdown($filters),
            'trend' => $this->reportsService->activityTrend($filters),
            'roleDistribution' => $this->reportsService->roleDistribution(),
            'activities' => $query->paginate(12)->withQueryString(),
        ]);
    }

    public function export(Request $request, string $format): BinaryFileResponse|Response
    {
        $filters = $this->reportsService->filtersFromRequest($request);
        $rows = $this->reportsService->exportRows($filters);
        $headings = ['Date', 'Actor', 'Module', 'Event', 'Description'];
        $filename = 'reports-'.now()->format('Ymd-His');
        $subtitle = 'Activity reporting snapshot';
        $meta = [
            'Range' => $filters['date_from']->format('M d, Y').' - '.$filters['date_to']->format('M d, Y'),
            'Rows' => (string) count($rows),
        ];
        $pdfBranding = $this->settingsService->pdfBranding([
            'export_title' => 'Reports Export',
            'export_subtitle' => $subtitle,
        ]);
        $excelBranding = $this->settingsService->excelBranding([
            'export_title' => 'Reports Export',
            'export_subtitle' => $subtitle,
        ]);

        return match ($format) {
            'xlsx' => Excel::download(new AdminCollectionExport($headings, $rows, $excelBranding, $meta), $filename.'.xlsx'),
            'pdf' => Pdf::loadView('admin.exports.table', [
                'title' => 'Reports Export',
                'subtitle' => $subtitle,
                'headings' => $headings,
                'rows' => $rows,
                'branding' => $pdfBranding,
                'meta' => $meta,
            ])->download($filename.'.pdf'),
        };
    }
}