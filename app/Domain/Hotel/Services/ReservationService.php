<?php

namespace App\Domain\Hotel\Services;

use App\Domain\Hotel\Contracts\ReservationRepository;
use App\Domain\Hotel\Events\ReservationConfirmed;
use App\Domain\Hotel\Models\Reservation;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class ReservationService
{
    public function __construct(
        protected ReservationRepository $reservationRepository,
        protected ReservationConflictService $reservationConflictService,
    ) {
    }

    public function create(array $payload): Reservation
    {
        $checkInDate = Carbon::parse($payload['check_in_date']);
        $checkOutDate = Carbon::parse($payload['check_out_date']);

        if ($checkOutDate->lessThanOrEqualTo($checkInDate)) {
            throw new InvalidArgumentException('Check-out date must be after check-in date.');
        }

        if ($this->reservationConflictService->hasConflict((int) $payload['room_id'], $checkInDate, $checkOutDate)) {
            throw new InvalidArgumentException('The selected room is already reserved for the requested dates.');
        }

        $payload['night_count'] = $payload['night_count'] ?? $checkInDate->diffInDays($checkOutDate);
        $payload['total_amount'] = $payload['total_amount'] ?? (($payload['rate_amount'] ?? 0) + ($payload['tax_amount'] ?? 0));

        $reservation = $this->reservationRepository->create($payload);

        if (($payload['status'] ?? Reservation::STATUS_PENDING) === Reservation::STATUS_CONFIRMED) {
            event(new ReservationConfirmed($reservation));
        }

        return $reservation;
    }
}