<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Accounting\Models\Invoice;
use App\Domain\Accounting\Services\AccountsReceivableService;
use App\Domain\Hotel\Models\Folio;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InvoiceController extends Controller
{
    public function __construct(protected AccountsReceivableService $accountsReceivableService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Invoice::query()->with(['lines', 'payments', 'refunds']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return InvoiceResource::collection($query->latest()->paginate());
    }

    public function store(StoreInvoiceRequest $request): InvoiceResource
    {
        $folio = Folio::query()->findOrFail($request->integer('folio_id'));
        $invoice = $this->accountsReceivableService->issueInvoiceFromFolio($folio, $request->validated());

        return new InvoiceResource($invoice->load(['lines', 'payments', 'refunds']));
    }

    public function show(Invoice $invoice): InvoiceResource
    {
        return new InvoiceResource($invoice->load(['lines', 'payments', 'refunds']));
    }
}