<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Hotel\Models\Reservation;
use App\Domain\Hotel\Services\ReservationService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class ReservationController extends Controller
{
    public function __construct(
        protected ReservationService $reservationService,
        protected CurrentCompanyContext $companyContext,
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Reservation::query()->with(['property', 'room', 'guestProfile.identityDocuments.extractionRequests', 'identityDocuments.extractionRequests', 'visitors']);

        foreach (['property_id', 'room_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->integer($filter));
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return ReservationResource::collection($query->latest()->paginate());
    }

    public function store(StoreReservationRequest $request): ReservationResource
    {
        $this->ensureCompanyContext();

        $reservation = $this->reservationService->create($request->validated());

        return new ReservationResource($reservation->load(['property', 'room', 'guestProfile.identityDocuments.extractionRequests', 'identityDocuments.extractionRequests', 'visitors']));
    }

    public function show(Reservation $reservation): ReservationResource
    {
        return new ReservationResource($reservation->load(['property', 'room', 'guestProfile.identityDocuments.extractionRequests', 'identityDocuments.extractionRequests', 'visitors']));
    }

    public function update(UpdateReservationRequest $request, Reservation $reservation): ReservationResource
    {
        $reservation->update($request->validated());

        return new ReservationResource($reservation->fresh()->load(['property', 'room', 'guestProfile.identityDocuments.extractionRequests', 'identityDocuments.extractionRequests', 'visitors']));
    }

    public function destroy(Reservation $reservation): \Illuminate\Http\JsonResponse
    {
        $reservation->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    protected function ensureCompanyContext(): void
    {
        abort_if($this->companyContext->id() === null, Response::HTTP_UNPROCESSABLE_ENTITY, 'Select a company context before creating tenant records.');
    }
}