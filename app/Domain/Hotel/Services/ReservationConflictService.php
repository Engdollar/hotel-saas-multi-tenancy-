<?php

namespace App\Domain\Hotel\Services;

use App\Domain\Hotel\Models\Reservation;
use Carbon\CarbonInterface;

class ReservationConflictService
{
    public function hasConflict(int $roomId, CarbonInterface $checkInDate, CarbonInterface $checkOutDate, ?int $ignoreReservationId = null): bool
    {
        return Reservation::query()
            ->where('room_id', $roomId)
            ->whereIn('status', Reservation::activeStatuses())
            ->when($ignoreReservationId !== null, fn ($query) => $query->whereKeyNot($ignoreReservationId))
            ->whereDate('check_in_date', '<', $checkOutDate)
            ->whereDate('check_out_date', '>', $checkInDate)
            ->exists();
    }
}