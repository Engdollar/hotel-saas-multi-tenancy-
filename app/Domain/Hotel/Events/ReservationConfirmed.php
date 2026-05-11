<?php

namespace App\Domain\Hotel\Events;

use App\Domain\Hotel\Models\Reservation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReservationConfirmed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Reservation $reservation)
    {
    }
}