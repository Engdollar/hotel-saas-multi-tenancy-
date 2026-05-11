<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Hotel\Models\Reservation;
use App\Domain\Hotel\Services\ReservationOperationsService;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReservationCheckInRequest;
use App\Http\Requests\ReservationCheckOutRequest;
use App\Http\Requests\ReservationPreArrivalRegistrationRequest;
use App\Http\Requests\ReservationRoomMoveRequest;
use App\Http\Resources\ReservationResource;

class ReservationOperationsController extends Controller
{
    public function __construct(protected ReservationOperationsService $reservationOperationsService)
    {
    }

    public function checkIn(ReservationCheckInRequest $request, Reservation $reservation): ReservationResource
    {
        $reservation = $this->reservationOperationsService->checkIn($reservation, $request->user(), $request->validated());

        return new ReservationResource($reservation);
    }

    public function preArrivalRegistration(ReservationPreArrivalRegistrationRequest $request, Reservation $reservation): ReservationResource
    {
        $reservation = $this->reservationOperationsService->submitPreArrivalRegistration($reservation, $request->user(), $request->validated());

        return new ReservationResource($reservation);
    }

    public function checkOut(ReservationCheckOutRequest $request, Reservation $reservation): ReservationResource
    {
        $reservation = $this->reservationOperationsService->checkOut($reservation, $request->user(), $request->validated());

        return new ReservationResource($reservation);
    }

    public function moveRoom(ReservationRoomMoveRequest $request, Reservation $reservation): ReservationResource
    {
        $reservation = $this->reservationOperationsService->moveRoom(
            $reservation,
            $request->integer('to_room_id'),
            $request->user(),
            $request->validated('reason'),
        );

        return new ReservationResource($reservation);
    }
}