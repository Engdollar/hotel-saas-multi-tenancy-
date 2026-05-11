<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Hotel\Models\Room;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Http\Resources\RoomResource;
use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class RoomController extends Controller
{
    public function __construct(protected CurrentCompanyContext $companyContext)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Room::query()->with('property');

        if ($request->filled('property_id')) {
            $query->where('property_id', $request->integer('property_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return RoomResource::collection($query->latest()->paginate());
    }

    public function store(StoreRoomRequest $request): RoomResource
    {
        $this->ensureCompanyContext();

        $room = Room::query()->create($request->validated());

        return new RoomResource($room->load('property'));
    }

    public function show(Room $room): RoomResource
    {
        return new RoomResource($room->load('property'));
    }

    public function update(UpdateRoomRequest $request, Room $room): RoomResource
    {
        $room->update($request->validated());

        return new RoomResource($room->fresh()->load('property'));
    }

    public function destroy(Room $room): \Illuminate\Http\JsonResponse
    {
        $room->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    protected function ensureCompanyContext(): void
    {
        abort_if($this->companyContext->id() === null, Response::HTTP_UNPROCESSABLE_ENTITY, 'Select a company context before creating tenant records.');
    }
}