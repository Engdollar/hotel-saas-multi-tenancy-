<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Models\BankAccount;
use App\Domain\Accounting\Models\BankReconciliation;
use App\Domain\Accounting\Models\Payment;
use App\Domain\Accounting\Models\SupplierPayment;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class BankReconciliationService
{
    public function create(array $attributes): BankReconciliation
    {
        return DB::transaction(function () use ($attributes) {
            $lines = $attributes['lines'] ?? [];
            unset($attributes['lines']);

            $bankAccount = BankAccount::query()->findOrFail($attributes['bank_account_id']);

            $reconciliation = BankReconciliation::query()->create([
                ...$attributes,
                'book_ending_balance' => $attributes['book_ending_balance'] ?? $bankAccount->current_balance,
                'status' => $attributes['status'] ?? BankReconciliation::STATUS_OPEN,
            ]);

            $this->syncLines($reconciliation, $lines);

            return $this->recalculate($reconciliation);
        });
    }

    public function update(BankReconciliation $reconciliation, array $attributes): BankReconciliation
    {
        return DB::transaction(function () use ($reconciliation, $attributes) {
            $lines = $attributes['lines'] ?? null;
            unset($attributes['lines']);

            if (($attributes['status'] ?? null) === BankReconciliation::STATUS_COMPLETED) {
                $attributes['completed_at'] = $attributes['completed_at'] ?? now();
            }

            $reconciliation->update($attributes);

            if (is_array($lines)) {
                $reconciliation->lines()->delete();
                $this->syncLines($reconciliation, $lines);
            }

            return $this->recalculate($reconciliation);
        });
    }

    protected function syncLines(BankReconciliation $reconciliation, array $lines): void
    {
        foreach ($lines as $line) {
            $resolved = $this->resolveLinePayload($line);

            $reconciliation->lines()->create([
                'company_id' => $reconciliation->company_id,
                ...$resolved,
                'is_cleared' => (bool) ($line['is_cleared'] ?? false),
                'cleared_at' => ($line['is_cleared'] ?? false) ? ($line['cleared_at'] ?? now()) : null,
            ]);
        }
    }

    protected function resolveLinePayload(array $line): array
    {
        $referenceType = $line['reference_type'] ?? null;
        $referenceId = $line['reference_id'] ?? null;

        if ($referenceType === Payment::class && $referenceId) {
            $payment = Payment::query()->findOrFail($referenceId);

            return [
                'entry_type' => 'customer_payment',
                'reference_type' => Payment::class,
                'reference_id' => $payment->id,
                'description' => $line['description'] ?? ('Customer payment '.$payment->payment_number),
                'transaction_date' => $line['transaction_date'] ?? $payment->paid_at->toDateString(),
                'amount' => $line['amount'] ?? $payment->amount,
            ];
        }

        if ($referenceType === SupplierPayment::class && $referenceId) {
            $payment = SupplierPayment::query()->findOrFail($referenceId);

            return [
                'entry_type' => 'supplier_payment',
                'reference_type' => SupplierPayment::class,
                'reference_id' => $payment->id,
                'description' => $line['description'] ?? ('Supplier payment '.$payment->payment_number),
                'transaction_date' => $line['transaction_date'] ?? $payment->paid_at->toDateString(),
                'amount' => $line['amount'] ?? ((float) $payment->amount * -1),
            ];
        }

        return [
            'entry_type' => $line['entry_type'] ?? 'adjustment',
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $line['description'],
            'transaction_date' => $line['transaction_date'],
            'amount' => $line['amount'],
        ];
    }

    protected function recalculate(BankReconciliation $reconciliation): BankReconciliation
    {
        $clearedBalance = (float) $reconciliation->lines()->where('is_cleared', true)->sum('amount');
        $reconciliation->update(['cleared_balance' => $clearedBalance]);

        return $reconciliation->fresh(['bankAccount', 'lines']);
    }
}