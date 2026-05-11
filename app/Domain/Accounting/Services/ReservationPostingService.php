<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Contracts\JournalEntryRepository;
use App\Domain\Accounting\Models\JournalEntry;
use App\Domain\Accounting\Models\LedgerAccount;
use App\Domain\Hotel\Models\Reservation;
use Illuminate\Support\Facades\DB;

class ReservationPostingService
{
    public function __construct(protected JournalEntryRepository $journalEntryRepository)
    {
    }

    public function postReservationRevenue(Reservation $reservation): JournalEntry
    {
        return DB::transaction(function () use ($reservation) {
            $accounts = $this->resolveDefaultAccounts($reservation->currency_code ?? 'USD');

            return $this->journalEntryRepository->create([
                'company_id' => $reservation->company_id,
                'entry_date' => $reservation->check_in_date,
                'currency_code' => $reservation->currency_code ?? 'USD',
                'source_type' => Reservation::class,
                'source_id' => $reservation->id,
                'description' => 'Reservation revenue posting for '.$reservation->reservation_number,
                'status' => JournalEntry::STATUS_POSTED,
                'posted_at' => now(),
            ], [
                [
                    'company_id' => $reservation->company_id,
                    'ledger_account_id' => $accounts['receivable']->id,
                    'description' => 'Guest receivable',
                    'debit_amount' => $reservation->total_amount,
                    'credit_amount' => 0,
                ],
                [
                    'company_id' => $reservation->company_id,
                    'ledger_account_id' => $accounts['revenue']->id,
                    'description' => 'Room revenue',
                    'debit_amount' => 0,
                    'credit_amount' => $reservation->total_amount,
                ],
            ]);
        });
    }

    protected function resolveDefaultAccounts(string $currencyCode): array
    {
        $receivable = LedgerAccount::query()->firstOrCreate(
            ['code' => '1100'],
            [
                'name' => 'Accounts Receivable',
                'type' => LedgerAccount::TYPE_ASSET,
                'subtype' => 'trade_receivable',
                'currency_code' => $currencyCode,
                'is_system' => true,
                'is_active' => true,
            ],
        );

        $revenue = LedgerAccount::query()->firstOrCreate(
            ['code' => '4000'],
            [
                'name' => 'Room Revenue',
                'type' => LedgerAccount::TYPE_INCOME,
                'subtype' => 'room_revenue',
                'currency_code' => $currencyCode,
                'is_system' => true,
                'is_active' => true,
            ],
        );

        return [
            'receivable' => $receivable,
            'revenue' => $revenue,
        ];
    }
}