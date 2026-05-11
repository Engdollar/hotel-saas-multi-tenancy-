<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Accounting\Models\SupplierBill;
use App\Domain\Accounting\Services\AccountsPayableService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierBillRequest;
use App\Http\Resources\SupplierBillResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SupplierBillController extends Controller
{
    public function __construct(protected AccountsPayableService $accountsPayableService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = SupplierBill::query()->with(['lines', 'payments']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return SupplierBillResource::collection($query->latest()->paginate());
    }

    public function store(StoreSupplierBillRequest $request): SupplierBillResource
    {
        $bill = $this->accountsPayableService->createSupplierBill($request->validated());

        return new SupplierBillResource($bill->load(['lines', 'payments']));
    }

    public function show(SupplierBill $supplierBill): SupplierBillResource
    {
        return new SupplierBillResource($supplierBill->load(['lines', 'payments']));
    }
}