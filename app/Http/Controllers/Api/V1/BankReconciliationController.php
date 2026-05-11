<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Accounting\Models\BankReconciliation;
use App\Domain\Accounting\Services\BankReconciliationService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBankReconciliationRequest;
use App\Http\Requests\UpdateBankReconciliationRequest;
use App\Http\Resources\BankReconciliationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class BankReconciliationController extends Controller
{
    public function __construct(protected BankReconciliationService $bankReconciliationService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = BankReconciliation::query()->with(['bankAccount', 'lines']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return BankReconciliationResource::collection($query->latest('period_end')->paginate());
    }

    public function store(StoreBankReconciliationRequest $request): BankReconciliationResource
    {
        $reconciliation = $this->bankReconciliationService->create($request->validated());

        return new BankReconciliationResource($reconciliation);
    }

    public function show(BankReconciliation $bankReconciliation): BankReconciliationResource
    {
        return new BankReconciliationResource($bankReconciliation->load(['bankAccount', 'lines']));
    }

    public function update(UpdateBankReconciliationRequest $request, BankReconciliation $bankReconciliation): BankReconciliationResource
    {
        $reconciliation = $this->bankReconciliationService->update($bankReconciliation, $request->validated());

        return new BankReconciliationResource($reconciliation);
    }

    public function destroy(BankReconciliation $bankReconciliation): \Illuminate\Http\JsonResponse
    {
        $bankReconciliation->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}