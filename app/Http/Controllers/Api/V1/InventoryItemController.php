<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Inventory\Models\InventoryItem;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInventoryItemRequest;
use App\Http\Requests\UpdateInventoryItemRequest;
use App\Http\Resources\InventoryItemResource;
use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class InventoryItemController extends Controller
{
    public function __construct(protected CurrentCompanyContext $companyContext)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = InventoryItem::query()->with(['preferredSupplier', 'movements']);

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        return InventoryItemResource::collection($query->latest()->paginate());
    }

    public function store(StoreInventoryItemRequest $request): InventoryItemResource
    {
        $this->ensureCompanyContext();

        $item = InventoryItem::query()->create($request->validated());

        return new InventoryItemResource($item->load(['preferredSupplier', 'movements']));
    }

    public function show(InventoryItem $inventoryItem): InventoryItemResource
    {
        return new InventoryItemResource($inventoryItem->load(['preferredSupplier', 'movements']));
    }

    public function update(UpdateInventoryItemRequest $request, InventoryItem $inventoryItem): InventoryItemResource
    {
        $inventoryItem->update($request->validated());

        return new InventoryItemResource($inventoryItem->fresh()->load(['preferredSupplier', 'movements']));
    }

    public function destroy(InventoryItem $inventoryItem): \Illuminate\Http\JsonResponse
    {
        $inventoryItem->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    protected function ensureCompanyContext(): void
    {
        abort_if($this->companyContext->id() === null, Response::HTTP_UNPROCESSABLE_ENTITY, 'Select a company context before creating tenant records.');
    }
}