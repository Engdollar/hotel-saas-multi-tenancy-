<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AdminCollectionExport;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AdminDataExportService;
use App\Services\SettingsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataExportController extends Controller
{
    public function __construct(
        protected AdminDataExportService $exportService,
        protected SettingsService $settingsService,
    )
    {
    }

    public function users(Request $request, string $format): StreamedResponse|BinaryFileResponse|Response
    {
        $this->authorize('viewAny', User::class);

        return $this->export($this->exportService->users($request->string('search')->toString()), $format);
    }

    public function roles(Request $request, string $format): StreamedResponse|BinaryFileResponse|Response
    {
        $this->authorize('viewAny', Role::class);

        return $this->export($this->exportService->roles($request->string('search')->toString()), $format);
    }

    public function permissions(Request $request, string $format): StreamedResponse|BinaryFileResponse|Response
    {
        $this->authorize('viewAny', Permission::class);

        return $this->export($this->exportService->permissions($request->string('search')->toString()), $format);
    }

    protected function export(array $dataset, string $format): StreamedResponse|BinaryFileResponse|Response
    {
        $filename = $dataset['filename'].'-'.now()->format('Ymd-His');
        $meta = [
            'Rows' => (string) collect($dataset['rows'])->count(),
            'Export' => str($dataset['filename'])->replace('-', ' ')->title()->toString(),
        ];
        $pdfBranding = $this->settingsService->pdfBranding([
            'export_title' => $dataset['title'],
            'export_subtitle' => 'Admin data export',
        ]);
        $excelBranding = $this->settingsService->excelBranding([
            'export_title' => $dataset['title'],
            'export_subtitle' => 'Admin data export',
        ]);

        return match ($format) {
            'csv' => response()->streamDownload(function () use ($dataset) {
                $stream = fopen('php://output', 'w');
                fputcsv($stream, $dataset['headings']);

                foreach ($dataset['rows'] as $row) {
                    fputcsv($stream, $row);
                }

                fclose($stream);
            }, $filename.'.csv', ['Content-Type' => 'text/csv']),
            'xlsx' => Excel::download(new AdminCollectionExport($dataset['headings'], $dataset['rows'], $excelBranding, $meta), $filename.'.xlsx'),
            'pdf' => Pdf::loadView('admin.exports.table', [
                'title' => $dataset['title'],
                'subtitle' => 'Admin data export',
                'headings' => $dataset['headings'],
                'rows' => $dataset['rows'],
                'branding' => $pdfBranding,
                'meta' => $meta,
            ])->download($filename.'.pdf'),
        };
    }
}