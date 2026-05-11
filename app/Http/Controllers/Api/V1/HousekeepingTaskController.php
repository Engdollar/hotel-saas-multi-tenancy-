<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Hotel\Models\HousekeepingTask;
use App\Domain\Hotel\Services\HousekeepingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHousekeepingTaskRequest;
use App\Http\Requests\UpdateHousekeepingTaskRequest;
use App\Http\Resources\HousekeepingTaskResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class HousekeepingTaskController extends Controller
{
    public function __construct(protected HousekeepingService $housekeepingService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = HousekeepingTask::query()->with(['assignee', 'inspector', 'inspections']);

        foreach (['status', 'task_type', 'room_id', 'assigned_to_user_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        return HousekeepingTaskResource::collection($query->latest()->paginate());
    }

    public function store(StoreHousekeepingTaskRequest $request): HousekeepingTaskResource
    {
        $task = HousekeepingTask::query()->create($request->validated());

        return new HousekeepingTaskResource($task->fresh(['assignee', 'inspector', 'inspections']));
    }

    public function show(HousekeepingTask $housekeepingTask): HousekeepingTaskResource
    {
        return new HousekeepingTaskResource($housekeepingTask->load(['assignee', 'inspector', 'inspections']));
    }

    public function update(UpdateHousekeepingTaskRequest $request, HousekeepingTask $housekeepingTask): HousekeepingTaskResource
    {
        $task = $this->housekeepingService->updateTask($housekeepingTask, $request->validated());

        return new HousekeepingTaskResource($task);
    }

    public function destroy(HousekeepingTask $housekeepingTask): \Illuminate\Http\JsonResponse
    {
        $housekeepingTask->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}