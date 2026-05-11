<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Accounting\Models\Invoice;
use App\Domain\Accounting\Services\AccountsReceivableService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRefundRequest;
use App\Http\Resources\InvoiceResource;

class RefundController extends Controller
{
    public function __construct(protected AccountsReceivableService $accountsReceivableService)
    {
    }

    public function store(StoreRefundRequest $request, Invoice $invoice): InvoiceResource
    {
        $this->accountsReceivableService->recordRefund($invoice, $request->validated());

        return new InvoiceResource($invoice->fresh()->load(['lines', 'payments', 'refunds']));
    }
}