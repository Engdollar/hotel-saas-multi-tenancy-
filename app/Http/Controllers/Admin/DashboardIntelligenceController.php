<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportsService;
use Illuminate\View\View;

class DashboardIntelligenceController extends Controller
{
    public function __construct(protected ReportsService $reportsService)
    {
    }

    public function index(): View
    {
        $intelligence = $this->reportsService->intelligence();

        return view('admin.intelligence.index', [
            'highlights' => $intelligence['highlights'],
            'roleRisk' => $intelligence['roleRisk'],
            'recommendations' => $intelligence['recommendations'],
            'recentActivities' => $intelligence['recentActivities'],
            'activityTrend' => $intelligence['activityTrend'],
            'roleDistribution' => $intelligence['roleDistribution'],
        ]);
    }
}