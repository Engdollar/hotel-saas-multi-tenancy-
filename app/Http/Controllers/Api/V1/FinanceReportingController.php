<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Accounting\Services\FinanceReportingService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceReportingController extends Controller
{
    public function __construct(protected FinanceReportingService $financeReportingService)
    {
    }

    public function arAging(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->financeReportingService->arAgingSummary($request->string('as_of_date')->toString() ?: null),
        ]);
    }

    public function apAging(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->financeReportingService->apAgingSummary($request->string('as_of_date')->toString() ?: null),
        ]);
    }
}