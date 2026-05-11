<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Contracts\JournalEntryRepository;
use App\Domain\Accounting\Models\Invoice;
use App\Domain\Accounting\Models\Payment;
use App\Domain\Accounting\Models\Refund;
use App\Domain\Accounting\Models\JournalEntry;
use App\Domain\Hotel\Models\Folio;
use App\Domain\Hotel\Models\Reservation;
use Illuminate\Support\Facades\DB;

class AccountsReceivableService
{
    public function __construct(
        protected JournalEntryRepository $journalEntryRepository,
        protected AccountingAccountService $accountingAccountService,
    ) {
    }

    public function issueInvoiceFromFolio(Folio $folio, array $attributes = []): Invoice
    {
        return DB::transaction(function () use ($folio, $attributes) {
            $folio->loadMissing('lines', 'reservation');

            $invoice = Invoice::query()->create([
                'company_id' => $folio->company_id,
                'guest_profile_id' => $folio->guest_profile_id,
                'folio_id' => $folio->id,
                'status' => Invoice::STATUS_ISSUED,
                'currency_code' => $folio->currency_code,
                'issue_date' => $attributes['issue_date'] ?? now()->toDateString(),
                'due_date' => $attributes['due_date'] ?? now()->toDateString(),
                'subtotal_amount' => $folio->subtotal_amount,
                'tax_amount' => $folio->tax_amount,
                'total_amount' => $folio->total_amount,
                'balance_amount' => $folio->balance_amount,
                'source_type' => Folio::class,
                'source_id' => $folio->id,
                'notes' => $attributes['notes'] ?? null,
            ]);

            foreach ($folio->lines as $line) {
                $invoice->lines()->create([
                    'company_id' => $invoice->company_id,
                    'ledger_account_id' => $line->ledger_account_id,
                    'description' => $line->description,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'tax_amount' => $line->tax_amount,
                    'total_amount' => $line->total_amount,
                ]);
            }

            $folio->update([
                'status' => Folio::STATUS_INVOICED,
                'balance_amount' => $invoice->balance_amount,
            ]);

            if (! $this->reservationAlreadyPosted($folio->reservation)) {
                $this->postInvoiceRecognition($invoice);
            }

            return $invoice->load('lines');
        });
    }

    public function recordPayment(Invoice $invoice, array $attributes): Payment
    {
        return DB::transaction(function () use ($invoice, $attributes) {
            $payment = Payment::query()->create([
                'company_id' => $invoice->company_id,
                'invoice_id' => $invoice->id,
                'payment_method' => $attributes['payment_method'] ?? 'cash',
                'currency_code' => $invoice->currency_code,
                'paid_at' => $attributes['paid_at'] ?? now(),
                'amount' => $attributes['amount'],
                'reference' => $attributes['reference'] ?? null,
                'status' => Payment::STATUS_POSTED,
            ]);

            $remaining = max(0, (float) $invoice->balance_amount - (float) $payment->amount);
            $invoice->update([
                'balance_amount' => $remaining,
                'status' => $remaining <= 0 ? Invoice::STATUS_PAID : Invoice::STATUS_PARTIALLY_PAID,
            ]);

            $cash = $this->accountingAccountService->cash($invoice->currency_code);
            $receivable = $this->accountingAccountService->receivable($invoice->currency_code);

            $this->journalEntryRepository->create([
                'company_id' => $invoice->company_id,
                'entry_date' => $payment->paid_at->toDateString(),
                'currency_code' => $invoice->currency_code,
                'source_type' => Payment::class,
                'source_id' => $payment->id,
                'description' => 'Invoice payment for '.$invoice->invoice_number,
                'status' => JournalEntry::STATUS_POSTED,
                'posted_at' => now(),
            ], [
                [
                    'company_id' => $invoice->company_id,
                    'ledger_account_id' => $cash->id,
                    'description' => 'Cash received',
                    'debit_amount' => $payment->amount,
                    'credit_amount' => 0,
                ],
                [
                    'company_id' => $invoice->company_id,
                    'ledger_account_id' => $receivable->id,
                    'description' => 'Reduce accounts receivable',
                    'debit_amount' => 0,
                    'credit_amount' => $payment->amount,
                ],
            ]);

            return $payment;
        });
    }

    public function recordRefund(Invoice $invoice, array $attributes): Refund
    {
        return DB::transaction(function () use ($invoice, $attributes) {
            $refund = Refund::query()->create([
                'company_id' => $invoice->company_id,
                'invoice_id' => $invoice->id,
                'payment_id' => $attributes['payment_id'] ?? null,
                'currency_code' => $invoice->currency_code,
                'refunded_at' => $attributes['refunded_at'] ?? now(),
                'amount' => $attributes['amount'],
                'reason' => $attributes['reason'] ?? 'Refund issued',
                'status' => Refund::STATUS_POSTED,
            ]);

            $invoice->update([
                'balance_amount' => max(0, (float) $invoice->balance_amount + (float) $refund->amount),
                'status' => Invoice::STATUS_REFUNDED,
            ]);

            $refunds = $this->accountingAccountService->refunds($invoice->currency_code);
            $cash = $this->accountingAccountService->cash($invoice->currency_code);

            $this->journalEntryRepository->create([
                'company_id' => $invoice->company_id,
                'entry_date' => $refund->refunded_at->toDateString(),
                'currency_code' => $invoice->currency_code,
                'source_type' => Refund::class,
                'source_id' => $refund->id,
                'description' => 'Invoice refund for '.$invoice->invoice_number,
                'status' => JournalEntry::STATUS_POSTED,
                'posted_at' => now(),
            ], [
                [
                    'company_id' => $invoice->company_id,
                    'ledger_account_id' => $refunds->id,
                    'description' => 'Refund expense',
                    'debit_amount' => $refund->amount,
                    'credit_amount' => 0,
                ],
                [
                    'company_id' => $invoice->company_id,
                    'ledger_account_id' => $cash->id,
                    'description' => 'Cash refunded',
                    'debit_amount' => 0,
                    'credit_amount' => $refund->amount,
                ],
            ]);

            return $refund;
        });
    }

    protected function postInvoiceRecognition(Invoice $invoice): void
    {
        $receivable = $this->accountingAccountService->receivable($invoice->currency_code);
        $revenue = $this->accountingAccountService->roomRevenue($invoice->currency_code);
        $entryDate = (string) $invoice->issue_date;

        $this->journalEntryRepository->create([
            'company_id' => $invoice->company_id,
            'entry_date' => $entryDate,
            'currency_code' => $invoice->currency_code,
            'source_type' => Invoice::class,
            'source_id' => $invoice->id,
            'description' => 'Invoice recognition for '.$invoice->invoice_number,
            'status' => JournalEntry::STATUS_POSTED,
            'posted_at' => now(),
        ], [
            [
                'company_id' => $invoice->company_id,
                'ledger_account_id' => $receivable->id,
                'description' => 'Guest receivable',
                'debit_amount' => $invoice->total_amount,
                'credit_amount' => 0,
            ],
            [
                'company_id' => $invoice->company_id,
                'ledger_account_id' => $revenue->id,
                'description' => 'Operating revenue',
                'debit_amount' => 0,
                'credit_amount' => $invoice->total_amount,
            ],
        ]);
    }

    protected function reservationAlreadyPosted(?Reservation $reservation): bool
    {
        if (! $reservation) {
            return false;
        }

        return JournalEntry::query()
            ->where('source_type', Reservation::class)
            ->where('source_id', $reservation->id)
            ->exists();
    }
}