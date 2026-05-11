<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Hotel\Models\PosCashierShift;
use App\Domain\Hotel\Services\PosService;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClosePosCashierShiftRequest;
use App\Http\Requests\StorePosCashierShiftRequest;
use App\Http\Resources\PosCashierShiftResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class PosCashierShiftController extends Controller
{
    public function __construct(protected PosService $posService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = PosCashierShift::query()->with(['cashier', 'orders.lines']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return PosCashierShiftResource::collection($query->latest()->paginate());
    }

    public function store(StorePosCashierShiftRequest $request): PosCashierShiftResource
    {
        $shift = $this->posService->openShift($request->validated());

        return new PosCashierShiftResource($shift->load(['cashier', 'orders.lines']));
    }

    public function show(PosCashierShift $posCashierShift): PosCashierShiftResource
    {
        return new PosCashierShiftResource($posCashierShift->load(['cashier', 'orders.lines']));
    }

    public function close(ClosePosCashierShiftRequest $request, PosCashierShift $posCashierShift): PosCashierShiftResource
    {
        $shift = $this->posService->closeShift($posCashierShift, $request->validated());

        return new PosCashierShiftResource($shift);
    }

    public function destroy(PosCashierShift $posCashierShift): \Illuminate\Http\JsonResponse
    {
        $posCashierShift->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}