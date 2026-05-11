<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Contracts\JournalEntryRepository;
use App\Domain\Accounting\Models\JournalEntry;
use App\Domain\Accounting\Models\SupplierBill;
use App\Domain\Accounting\Models\SupplierPayment;
use App\Domain\Inventory\Models\PurchaseOrder;
use App\Domain\Inventory\Models\PurchaseOrderLine;
use App\Domain\Inventory\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AccountsPayableService
{
    public function __construct(
        protected JournalEntryRepository $journalEntryRepository,
        protected AccountingAccountService $accountingAccountService,
        protected InventoryService $inventoryService,
    ) {
    }

    public function createSupplierBill(array $attributes): SupplierBill
    {
        return DB::transaction(function () use ($attributes) {
            $lineItems = $attributes['lines'] ?? [];
            $purchaseOrderId = $attributes['purchase_order_id'] ?? null;
            unset($attributes['lines']);

            $purchaseOrder = $purchaseOrderId
                ? PurchaseOrder::query()->with('lines')->findOrFail($purchaseOrderId)
                : null;

            if ($purchaseOrder && (int) $purchaseOrder->supplier_id !== (int) $attributes['supplier_id']) {
                throw new InvalidArgumentException('Supplier bill supplier must match the linked purchase order supplier.');
            }

            $lineMatchStatuses = [];

            $subtotal = collect($lineItems)->sum(fn (array $line) => ((float) $line['quantity'] * (float) $line['unit_cost']));
            $tax = collect($lineItems)->sum(fn (array $line) => (float) ($line['tax_amount'] ?? 0));
            $total = collect($lineItems)->sum(fn (array $line) => (float) ($line['total_amount'] ?? (((float) $line['quantity'] * (float) $line['unit_cost']) + (float) ($line['tax_amount'] ?? 0))));

            $bill = SupplierBill::query()->create([
                ...$attributes,
                'status' => $attributes['status'] ?? SupplierBill::STATUS_APPROVED,
                'purchase_order_id' => $purchaseOrder?->id,
                'match_status' => $purchaseOrder ? SupplierBill::MATCH_STATUS_UNMATCHED : SupplierBill::MATCH_STATUS_UNMATCHED,
                'subtotal_amount' => $subtotal,
                'tax_amount' => $tax,
                'total_amount' => $total,
                'balance_amount' => $total,
            ]);

            foreach ($lineItems as $line) {
                $purchaseOrderLine = null;

                if ($purchaseOrder && isset($line['purchase_order_line_id'])) {
                    $purchaseOrderLine = $purchaseOrder->lines->firstWhere('id', (int) $line['purchase_order_line_id']);

                    if (! $purchaseOrderLine) {
                        throw new InvalidArgumentException('Supplier bill line must reference a line on the linked purchase order.');
                    }
                }

                $amount = (float) ($line['total_amount'] ?? (((float) $line['quantity'] * (float) $line['unit_cost']) + (float) ($line['tax_amount'] ?? 0)));
                $bill->lines()->create([
                    'company_id' => $bill->company_id,
                    'purchase_order_line_id' => $purchaseOrderLine?->id,
                    'inventory_item_id' => $line['inventory_item_id'] ?? $purchaseOrderLine?->inventory_item_id,
                    'ledger_account_id' => $line['ledger_account_id'] ?? $this->accountingAccountService->operatingExpense($bill->currency_code)->id,
                    'description' => $line['description'],
                    'quantity' => $line['quantity'],
                    'unit_cost' => $line['unit_cost'],
                    'tax_amount' => $line['tax_amount'] ?? 0,
                    'total_amount' => $amount,
                ]);

                if ($purchaseOrderLine) {
                    $lineMatchStatuses[] = $this->evaluateThreeWayMatch(
                        $purchaseOrder,
                        $purchaseOrderLine,
                        (float) $purchaseOrderLine->billed_quantity + (float) $line['quantity'],
                        $amount,
                    );

                    $purchaseOrderLine->update([
                        'billed_quantity' => (float) $purchaseOrderLine->billed_quantity + (float) $line['quantity'],
                    ]);
                }
            }

            if ($purchaseOrder) {
                $bill->update([
                    'match_status' => $this->aggregateBillMatchStatus($lineMatchStatuses),
                ]);

                $this->inventoryService->syncPurchaseOrderBillingStatus($purchaseOrder);
            }

            $expense = $this->accountingAccountService->operatingExpense($bill->currency_code);
            $payable = $this->accountingAccountService->payable($bill->currency_code);
            $entryDate = (string) $bill->bill_date;

            $this->journalEntryRepository->create([
                'company_id' => $bill->company_id,
                'entry_date' => $entryDate,
                'currency_code' => $bill->currency_code,
                'source_type' => SupplierBill::class,
                'source_id' => $bill->id,
                'description' => 'Supplier bill '.$bill->bill_number,
                'status' => JournalEntry::STATUS_POSTED,
                'posted_at' => now(),
            ], [
                [
                    'company_id' => $bill->company_id,
                    'ledger_account_id' => $expense->id,
                    'description' => 'Recognize expense',
                    'debit_amount' => $bill->total_amount,
                    'credit_amount' => 0,
                ],
                [
                    'company_id' => $bill->company_id,
                    'ledger_account_id' => $payable->id,
                    'description' => 'Recognize accounts payable',
                    'debit_amount' => 0,
                    'credit_amount' => $bill->total_amount,
                ],
            ]);

            return $bill->load('lines');
        });
    }

    public function recordSupplierPayment(SupplierBill $bill, array $attributes): SupplierPayment
    {
        return DB::transaction(function () use ($bill, $attributes) {
            $payment = SupplierPayment::query()->create([
                'company_id' => $bill->company_id,
                'supplier_bill_id' => $bill->id,
                'payment_method' => $attributes['payment_method'] ?? 'bank_transfer',
                'currency_code' => $bill->currency_code,
                'paid_at' => $attributes['paid_at'] ?? now(),
                'amount' => $attributes['amount'],
                'reference' => $attributes['reference'] ?? null,
                'status' => SupplierPayment::STATUS_POSTED,
            ]);

            $remaining = max(0, (float) $bill->balance_amount - (float) $payment->amount);
            $bill->update([
                'balance_amount' => $remaining,
                'status' => $remaining <= 0 ? SupplierBill::STATUS_PAID : SupplierBill::STATUS_PARTIALLY_PAID,
            ]);

            $payable = $this->accountingAccountService->payable($bill->currency_code);
            $cash = $this->accountingAccountService->cash($bill->currency_code);

            $this->journalEntryRepository->create([
                'company_id' => $bill->company_id,
                'entry_date' => $payment->paid_at->toDateString(),
                'currency_code' => $bill->currency_code,
                'source_type' => SupplierPayment::class,
                'source_id' => $payment->id,
                'description' => 'Supplier payment for '.$bill->bill_number,
                'status' => JournalEntry::STATUS_POSTED,
                'posted_at' => now(),
            ], [
                [
                    'company_id' => $bill->company_id,
                    'ledger_account_id' => $payable->id,
                    'description' => 'Reduce accounts payable',
                    'debit_amount' => $payment->amount,
                    'credit_amount' => 0,
                ],
                [
                    'company_id' => $bill->company_id,
                    'ledger_account_id' => $cash->id,
                    'description' => 'Cash disbursed',
                    'debit_amount' => 0,
                    'credit_amount' => $payment->amount,
                ],
            ]);

            return $payment;
        });
    }

    protected function evaluateThreeWayMatch(PurchaseOrder $purchaseOrder, PurchaseOrderLine $purchaseOrderLine, float $newBilledQuantity, float $lineAmount): string
    {
        $expectedQuantity = (float) ($purchaseOrderLine->received_quantity > 0 ? $purchaseOrderLine->received_quantity : $purchaseOrderLine->ordered_quantity);
        $expectedAmount = $newBilledQuantity * (float) $purchaseOrderLine->unit_cost;

        if ($expectedQuantity <= 0) {
            return SupplierBill::MATCH_STATUS_EXCEPTION;
        }

        $quantityVariancePercent = $this->variancePercent($newBilledQuantity, $expectedQuantity);
        $amountVariancePercent = $expectedAmount > 0 ? $this->variancePercent($lineAmount, $expectedAmount) : 0.0;

        if ($quantityVariancePercent === 0.0 && $amountVariancePercent === 0.0) {
            return SupplierBill::MATCH_STATUS_MATCHED;
        }

        if (
            $quantityVariancePercent <= (float) $purchaseOrder->quantity_tolerance_percent
            && $amountVariancePercent <= (float) $purchaseOrder->amount_tolerance_percent
        ) {
            return SupplierBill::MATCH_STATUS_WITHIN_TOLERANCE;
        }

        return SupplierBill::MATCH_STATUS_EXCEPTION;
    }

    protected function aggregateBillMatchStatus(array $lineMatchStatuses): string
    {
        if ($lineMatchStatuses === []) {
            return SupplierBill::MATCH_STATUS_UNMATCHED;
        }

        if (in_array(SupplierBill::MATCH_STATUS_EXCEPTION, $lineMatchStatuses, true)) {
            return SupplierBill::MATCH_STATUS_EXCEPTION;
        }

        if (in_array(SupplierBill::MATCH_STATUS_WITHIN_TOLERANCE, $lineMatchStatuses, true)) {
            return SupplierBill::MATCH_STATUS_WITHIN_TOLERANCE;
        }

        return SupplierBill::MATCH_STATUS_MATCHED;
    }

    protected function variancePercent(float $actual, float $expected): float
    {
        if ($expected == 0.0) {
            return $actual == 0.0 ? 0.0 : 100.0;
        }

        return round((abs($actual - $expected) / $expected) * 100, 4);
    }
}