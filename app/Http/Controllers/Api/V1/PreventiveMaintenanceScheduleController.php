<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Hotel\Models\PreventiveMaintenanceSchedule;
use App\Domain\Hotel\Services\MaintenanceService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePreventiveMaintenanceScheduleRequest;
use App\Http\Requests\UpdatePreventiveMaintenanceScheduleRequest;
use App\Http\Resources\MaintenanceRequestResource;
use App\Http\Resources\PreventiveMaintenanceScheduleResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class PreventiveMaintenanceScheduleController extends Controller
{
    public function __construct(protected MaintenanceService $maintenanceService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = PreventiveMaintenanceSchedule::query();

        foreach (['property_id', 'room_id', 'assigned_to_user_id', 'is_active'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        return PreventiveMaintenanceScheduleResource::collection($query->latest()->paginate());
    }

    public function store(StorePreventiveMaintenanceScheduleRequest $request): PreventiveMaintenanceScheduleResource
    {
        $schedule = PreventiveMaintenanceSchedule::query()->create($request->validated());

        return new PreventiveMaintenanceScheduleResource($schedule->fresh(['property', 'room', 'assignee']));
    }

    public function show(PreventiveMaintenanceSchedule $preventiveMaintenanceSchedule): PreventiveMaintenanceScheduleResource
    {
        return new PreventiveMaintenanceScheduleResource($preventiveMaintenanceSchedule->load(['property', 'room', 'assignee']));
    }

    public function update(UpdatePreventiveMaintenanceScheduleRequest $request, PreventiveMaintenanceSchedule $preventiveMaintenanceSchedule): PreventiveMaintenanceScheduleResource
    {
        $preventiveMaintenanceSchedule->update($request->validated());

        return new PreventiveMaintenanceScheduleResource($preventiveMaintenanceSchedule->fresh(['property', 'room', 'assignee']));
    }

    public function destroy(PreventiveMaintenanceSchedule $preventiveMaintenanceSchedule): \Illuminate\Http\JsonResponse
    {
        $preventiveMaintenanceSchedule->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    public function generate(PreventiveMaintenanceSchedule $preventiveMaintenanceSchedule, Request $request): MaintenanceRequestResource
    {
        $record = $this->maintenanceService->generateFromSchedule($preventiveMaintenanceSchedule, $request->user());

        return new MaintenanceRequestResource($record);
    }
}