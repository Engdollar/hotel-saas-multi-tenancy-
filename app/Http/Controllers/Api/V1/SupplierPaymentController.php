<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Accounting\Models\SupplierBill;
use App\Domain\Accounting\Services\AccountsPayableService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierPaymentRequest;
use App\Http\Resources\SupplierBillResource;

class SupplierPaymentController extends Controller
{
    public function __construct(protected AccountsPayableService $accountsPayableService)
    {
    }

    public function store(StoreSupplierPaymentRequest $request, SupplierBill $supplierBill): SupplierBillResource
    {
        $this->accountsPayableService->recordSupplierPayment($supplierBill, $request->validated());

        return new SupplierBillResource($supplierBill->fresh()->load(['lines', 'payments']));
    }
}