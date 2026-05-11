<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Services\ReportsService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected ReportsService $reportsService,
        protected DashboardService $dashboardService,
    )
    {
    }

    public function index(): View
    {
        return view('admin.dashboard', $this->dashboardService->buildDashboard(auth()->user()));
    }
}