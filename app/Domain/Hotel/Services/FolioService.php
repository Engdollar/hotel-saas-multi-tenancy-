<?php

namespace App\Domain\Hotel\Services;

use App\Domain\Accounting\Services\AccountingAccountService;
use App\Domain\Hotel\Models\Folio;
use App\Domain\Hotel\Models\Reservation;
use Illuminate\Support\Facades\DB;

class FolioService
{
    public function __construct(protected AccountingAccountService $accountingAccountService)
    {
    }

    public function openForReservation(Reservation $reservation): Folio
    {
        return Folio::query()->firstOrCreate(
            [
                'reservation_id' => $reservation->id,
            ],
            [
                'guest_profile_id' => $reservation->guest_profile_id,
                'currency_code' => $reservation->currency_code ?? 'USD',
                'status' => Folio::STATUS_OPEN,
                'subtotal_amount' => 0,
                'tax_amount' => 0,
                'total_amount' => 0,
                'balance_amount' => 0,
            ],
        );
    }

    public function addCharge(Folio $folio, array $attributes)
    {
        return DB::transaction(function () use ($folio, $attributes) {
            $quantity = (float) ($attributes['quantity'] ?? 1);
            $unitPrice = (float) ($attributes['unit_price'] ?? 0);
            $taxAmount = (float) ($attributes['tax_amount'] ?? 0);
            $totalAmount = (float) ($attributes['total_amount'] ?? (($quantity * $unitPrice) + $taxAmount));

            $line = $folio->lines()->create([
                'company_id' => $folio->company_id,
                'ledger_account_id' => $attributes['ledger_account_id'] ?? $this->accountingAccountService->roomRevenue($folio->currency_code)->id,
                'line_type' => $attributes['line_type'] ?? 'room_charge',
                'description' => $attributes['description'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'service_date' => $attributes['service_date'] ?? now()->toDateString(),
            ]);

            $this->refreshTotals($folio->fresh());

            return $line;
        });
    }

    public function refreshTotals(Folio $folio): Folio
    {
        $subtotal = (float) $folio->lines()->sum(DB::raw('(quantity * unit_price)'));
        $tax = (float) $folio->lines()->sum('tax_amount');
        $total = (float) $folio->lines()->sum('total_amount');

        $folio->update([
            'subtotal_amount' => $subtotal,
            'tax_amount' => $tax,
            'total_amount' => $total,
            'balance_amount' => $total,
        ]);

        return $folio->fresh(['lines']);
    }
}