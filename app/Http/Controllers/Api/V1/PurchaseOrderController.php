<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Inventory\Models\PurchaseOrder;
use App\Domain\Inventory\Services\InventoryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApprovePurchaseOrderRequest;
use App\Http\Requests\ReceivePurchaseOrderRequest;
use App\Http\Requests\RejectPurchaseOrderRequest;
use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Resources\PurchaseOrderResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class PurchaseOrderController extends Controller
{
    public function __construct(protected InventoryService $inventoryService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = PurchaseOrder::query()->with(['supplier', 'approver', 'approvals.approver', 'lines.item', 'receipts.lines']);

        foreach (['status', 'supplier_id', 'property_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        return PurchaseOrderResource::collection($query->latest()->paginate());
    }

    public function store(StorePurchaseOrderRequest $request): PurchaseOrderResource
    {
        $order = $this->inventoryService->createPurchaseOrder($request->validated());

        return new PurchaseOrderResource($order);
    }

    public function show(PurchaseOrder $purchaseOrder): PurchaseOrderResource
    {
        return new PurchaseOrderResource($purchaseOrder->load(['supplier', 'approver', 'approvals.approver', 'lines.item', 'receipts.lines']));
    }

    public function approve(ApprovePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): PurchaseOrderResource
    {
        $order = $this->inventoryService->approvePurchaseOrder($purchaseOrder, $request->user()?->id, $request->validated('notes'));

        return new PurchaseOrderResource($order);
    }

    public function reject(RejectPurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): PurchaseOrderResource
    {
        $order = $this->inventoryService->rejectPurchaseOrder($purchaseOrder, $request->user()?->id, $request->validated('notes'));

        return new PurchaseOrderResource($order);
    }

    public function receive(ReceivePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): PurchaseOrderResource
    {
        $order = $this->inventoryService->receivePurchaseOrder($purchaseOrder, $request->validated());

        return new PurchaseOrderResource($order);
    }

    public function destroy(PurchaseOrder $purchaseOrder): \Illuminate\Http\JsonResponse
    {
        $purchaseOrder->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}