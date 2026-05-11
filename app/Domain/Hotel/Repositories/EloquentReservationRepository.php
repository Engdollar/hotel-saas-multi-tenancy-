<?php

namespace App\Domain\Hotel\Repositories;

use App\Domain\Hotel\Contracts\ReservationRepository;
use App\Domain\Hotel\Models\Reservation;

class EloquentReservationRepository implements ReservationRepository
{
    public function create(array $attributes): Reservation
    {
        return Reservation::query()->create($attributes);
    }
}