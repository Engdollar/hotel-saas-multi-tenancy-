<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Hotel\Models\PosOrder;
use App\Domain\Hotel\Services\PosService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePosWastageRequest;
use App\Http\Requests\StorePosOrderRequest;
use App\Http\Requests\UpdatePosKitchenStatusRequest;
use App\Http\Requests\VoidPosOrderRequest;
use App\Http\Resources\PosOrderResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class PosOrderController extends Controller
{
    public function __construct(protected PosService $posService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = PosOrder::query()->with(['lines', 'folio', 'reservation', 'shift']);

        foreach (['status', 'cashier_shift_id', 'reservation_id', 'folio_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        return PosOrderResource::collection($query->latest()->paginate());
    }

    public function store(StorePosOrderRequest $request): PosOrderResource
    {
        $order = $this->posService->createOrder($request->validated());

        return new PosOrderResource($order);
    }

    public function show(PosOrder $posOrder): PosOrderResource
    {
        return new PosOrderResource($posOrder->load(['lines', 'folio.lines', 'reservation', 'shift']));
    }

    public function postToFolio(PosOrder $posOrder): PosOrderResource
    {
        $order = $this->posService->postOrderToFolio($posOrder->loadMissing('lines', 'folio', 'reservation', 'shift'));

        return new PosOrderResource($order);
    }

    public function sendToKitchen(UpdatePosKitchenStatusRequest $request, PosOrder $posOrder): PosOrderResource
    {
        $order = $this->posService->sendToKitchen($posOrder->loadMissing('lines', 'folio', 'reservation', 'shift'), $request->validated());

        return new PosOrderResource($order);
    }

    public function markKitchenReady(UpdatePosKitchenStatusRequest $request, PosOrder $posOrder): PosOrderResource
    {
        $order = $this->posService->markKitchenReady($posOrder->loadMissing('lines', 'folio', 'reservation', 'shift'), $request->validated());

        return new PosOrderResource($order);
    }

    public function void(VoidPosOrderRequest $request, PosOrder $posOrder): PosOrderResource
    {
        $order = $this->posService->voidOrder($posOrder->loadMissing('lines', 'folio', 'reservation', 'shift'), $request->validated());

        return new PosOrderResource($order);
    }

    public function recordWastage(StorePosWastageRequest $request, PosOrder $posOrder): PosOrderResource
    {
        $order = $this->posService->recordWastage($posOrder->loadMissing('lines', 'folio', 'reservation', 'shift'), $request->validated());

        return new PosOrderResource($order);
    }

    public function destroy(PosOrder $posOrder): \Illuminate\Http\JsonResponse
    {
        $posOrder->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}