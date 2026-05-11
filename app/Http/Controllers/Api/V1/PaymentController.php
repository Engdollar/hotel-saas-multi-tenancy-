<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Accounting\Models\Invoice;
use App\Domain\Accounting\Services\AccountsReceivableService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\InvoiceResource;

class PaymentController extends Controller
{
    public function __construct(protected AccountsReceivableService $accountsReceivableService)
    {
    }

    public function store(StorePaymentRequest $request, Invoice $invoice): InvoiceResource
    {
        $this->accountsReceivableService->recordPayment($invoice, $request->validated());

        return new InvoiceResource($invoice->fresh()->load(['lines', 'payments', 'refunds']));
    }
}