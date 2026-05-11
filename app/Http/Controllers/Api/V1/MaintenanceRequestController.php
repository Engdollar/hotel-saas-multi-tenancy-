<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Hotel\Models\MaintenanceRequest;
use App\Domain\Hotel\Services\MaintenanceService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMaintenanceRequest;
use App\Http\Requests\UpdateMaintenanceRequest;
use App\Http\Resources\MaintenanceRequestResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceRequestController extends Controller
{
    public function __construct(protected MaintenanceService $maintenanceService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = MaintenanceRequest::query()->with(['reporter', 'assignee', 'preventiveSchedule']);

        foreach (['status', 'priority', 'room_id', 'assigned_to_user_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        return MaintenanceRequestResource::collection($query->latest()->paginate());
    }

    public function store(StoreMaintenanceRequest $request): MaintenanceRequestResource
    {
        $record = $this->maintenanceService->create($request->validated());

        return new MaintenanceRequestResource($record);
    }

    public function show(MaintenanceRequest $maintenanceRequest): MaintenanceRequestResource
    {
        return new MaintenanceRequestResource($maintenanceRequest->load(['reporter', 'assignee', 'preventiveSchedule']));
    }

    public function update(UpdateMaintenanceRequest $request, MaintenanceRequest $maintenanceRequest): MaintenanceRequestResource
    {
        $record = $this->maintenanceService->update($maintenanceRequest, $request->validated());

        return new MaintenanceRequestResource($record);
    }

    public function destroy(MaintenanceRequest $maintenanceRequest): \Illuminate\Http\JsonResponse
    {
        $maintenanceRequest->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}