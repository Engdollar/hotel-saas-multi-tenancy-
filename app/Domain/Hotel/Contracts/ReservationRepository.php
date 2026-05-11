<?php

namespace App\Domain\Hotel\Contracts;

use App\Domain\Hotel\Models\Reservation;

interface ReservationRepository
{
    public function create(array $attributes): Reservation;
}