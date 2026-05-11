<?php

namespace App\Domain\Accounting\Listeners;

use App\Domain\Accounting\Services\ReservationPostingService;
use App\Domain\Hotel\Events\ReservationConfirmed;

class PostReservationRevenueEntry
{
    public function __construct(protected ReservationPostingService $reservationPostingService)
    {
    }

    public function handle(ReservationConfirmed $event): void
    {
        $this->reservationPostingService->postReservationRevenue($event->reservation);
    }
}