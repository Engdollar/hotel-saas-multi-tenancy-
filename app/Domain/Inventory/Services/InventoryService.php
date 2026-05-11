<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Accounting\Models\SupplierBill;
use App\Domain\Inventory\Models\GoodsReceipt;
use App\Domain\Inventory\Models\InventoryItem;
use App\Domain\Inventory\Models\InventoryMovement;
use App\Domain\Inventory\Models\PurchaseOrder;
use App\Domain\Inventory\Models\PurchaseOrderApproval;
use App\Domain\Inventory\Models\PurchaseOrderLine;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    public function createPurchaseOrder(array $attributes): PurchaseOrder
    {
        return DB::transaction(function () use ($attributes) {
            $lineItems = $attributes['lines'] ?? [];
            unset($attributes['lines']);

            if ($lineItems === []) {
                throw new InvalidArgumentException('Purchase orders require at least one line item.');
            }

            $subtotal = collect($lineItems)->sum(fn (array $line) => (float) $line['ordered_quantity'] * (float) $line['unit_cost']);
            $tax = collect($lineItems)->sum(fn (array $line) => (float) ($line['tax_amount'] ?? 0));
            $total = collect($lineItems)->sum(fn (array $line) => (float) ($line['total_amount'] ?? (((float) $line['ordered_quantity'] * (float) $line['unit_cost']) + (float) ($line['tax_amount'] ?? 0))));

            $order = PurchaseOrder::query()->create([
                ...$attributes,
                'status' => $attributes['status'] ?? PurchaseOrder::STATUS_DRAFT,
                'match_status' => PurchaseOrder::MATCH_STATUS_UNMATCHED,
                'subtotal_amount' => $subtotal,
                'tax_amount' => $tax,
                'total_amount' => $total,
                'quantity_tolerance_percent' => $attributes['quantity_tolerance_percent'] ?? 0,
                'amount_tolerance_percent' => $attributes['amount_tolerance_percent'] ?? 0,
            ]);

            foreach ($lineItems as $line) {
                $amount = (float) ($line['total_amount'] ?? (((float) $line['ordered_quantity'] * (float) $line['unit_cost']) + (float) ($line['tax_amount'] ?? 0)));

                $order->lines()->create([
                    'company_id' => $order->company_id,
                    'inventory_item_id' => $line['inventory_item_id'],
                    'description' => $line['description'],
                    'ordered_quantity' => $line['ordered_quantity'],
                    'received_quantity' => 0,
                    'billed_quantity' => 0,
                    'unit_cost' => $line['unit_cost'],
                    'tax_amount' => $line['tax_amount'] ?? 0,
                    'total_amount' => $amount,
                ]);
            }

            foreach (collect($attributes['approval_steps'] ?? [])->values() as $index => $step) {
                $order->approvals()->create([
                    'company_id' => $order->company_id,
                    'sequence_number' => $index + 1,
                    'approver_user_id' => $step['approver_user_id'],
                    'status' => PurchaseOrderApproval::STATUS_PENDING,
                    'notes' => $step['notes'] ?? null,
                ]);
            }

            return $order->load(['supplier', 'approver', 'approvals.approver', 'lines.item', 'receipts.lines']);
        });
    }

    public function approvePurchaseOrder(PurchaseOrder $order, ?int $approvedByUserId = null, ?string $notes = null): PurchaseOrder
    {
        if (in_array($order->status, [PurchaseOrder::STATUS_CANCELLED, PurchaseOrder::STATUS_REJECTED], true)) {
            throw new InvalidArgumentException('Cancelled purchase orders cannot be approved.');
        }

        $order->loadMissing('approvals');

        if ($order->status === PurchaseOrder::STATUS_APPROVED) {
            return $order->fresh(['supplier', 'approver', 'approvals.approver', 'lines.item', 'receipts.lines']);
        }

        if ($order->approvals->isNotEmpty()) {
            $pendingStep = $order->approvals
                ->where('status', PurchaseOrderApproval::STATUS_PENDING)
                ->sortBy('sequence_number')
                ->first();

            if (! $pendingStep) {
                return $order->fresh(['supplier', 'approver', 'approvals.approver', 'lines.item', 'receipts.lines']);
            }

            if ((int) $pendingStep->approver_user_id !== (int) $approvedByUserId) {
                throw new InvalidArgumentException('This purchase order is awaiting approval from a different approver.');
            }

            $pendingStep->update([
                'status' => PurchaseOrderApproval::STATUS_APPROVED,
                'acted_at' => now(),
                'notes' => $notes ?? $pendingStep->notes,
            ]);

            $remainingPending = $order->approvals()
                ->where('status', PurchaseOrderApproval::STATUS_PENDING)
                ->exists();

            if ($remainingPending) {
                return $order->fresh(['supplier', 'approver', 'approvals.approver', 'lines.item', 'receipts.lines']);
            }
        }

        $order->update([
            'status' => PurchaseOrder::STATUS_APPROVED,
            'approved_by_user_id' => $approvedByUserId,
            'approved_at' => now(),
        ]);

        return $order->fresh(['supplier', 'approver', 'approvals.approver', 'lines.item', 'receipts.lines']);
    }

    public function rejectPurchaseOrder(PurchaseOrder $order, ?int $rejectedByUserId = null, ?string $notes = null): PurchaseOrder
    {
        if (in_array($order->status, [PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_RECEIVED, PurchaseOrder::STATUS_PARTIALLY_RECEIVED], true)) {
            throw new InvalidArgumentException('Approved or received purchase orders cannot be rejected.');
        }

        $order->loadMissing('approvals');

        if ($order->approvals->isNotEmpty()) {
            $pendingStep = $order->approvals
                ->where('status', PurchaseOrderApproval::STATUS_PENDING)
                ->sortBy('sequence_number')
                ->first();

            if (! $pendingStep) {
                throw new InvalidArgumentException('This purchase order no longer has a pending approval step.');
            }

            if ((int) $pendingStep->approver_user_id !== (int) $rejectedByUserId) {
                throw new InvalidArgumentException('This purchase order is awaiting action from a different approver.');
            }

            $pendingStep->update([
                'status' => PurchaseOrderApproval::STATUS_REJECTED,
                'acted_at' => now(),
                'notes' => $notes ?? $pendingStep->notes,
            ]);
        }

        $order->update([
            'status' => PurchaseOrder::STATUS_REJECTED,
        ]);

        return $order->fresh(['supplier', 'approver', 'approvals.approver', 'lines.item', 'receipts.lines']);
    }

    public function receivePurchaseOrder(PurchaseOrder $order, array $attributes): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $attributes) {
            if (! in_array($order->status, [PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_PARTIALLY_RECEIVED, PurchaseOrder::STATUS_RECEIVED], true)) {
                throw new InvalidArgumentException('Only approved purchase orders can be received.');
            }

            $receiptLines = $attributes['lines'] ?? [];

            if ($receiptLines === []) {
                throw new InvalidArgumentException('Goods receipts require at least one line.');
            }

            $receipt = GoodsReceipt::query()->create([
                'company_id' => $order->company_id,
                'purchase_order_id' => $order->id,
                'received_at' => $attributes['received_at'] ?? now(),
                'notes' => $attributes['notes'] ?? null,
            ]);

            foreach ($receiptLines as $line) {
                $orderLine = $order->lines()->whereKey($line['purchase_order_line_id'])->firstOrFail();
                $receivedQuantity = (float) $line['received_quantity'];

                $receipt->lines()->create([
                    'company_id' => $order->company_id,
                    'purchase_order_line_id' => $orderLine->id,
                    'inventory_item_id' => $orderLine->inventory_item_id,
                    'received_quantity' => $receivedQuantity,
                    'unit_cost' => $line['unit_cost'] ?? $orderLine->unit_cost,
                ]);

                $orderLine->update([
                    'received_quantity' => (float) $orderLine->received_quantity + $receivedQuantity,
                ]);

                $item = InventoryItem::query()->findOrFail($orderLine->inventory_item_id);
                $item->update([
                    'current_quantity' => (float) $item->current_quantity + $receivedQuantity,
                    'unit_cost' => $line['unit_cost'] ?? $orderLine->unit_cost,
                ]);

                InventoryMovement::query()->create([
                    'company_id' => $order->company_id,
                    'inventory_item_id' => $item->id,
                    'movement_type' => InventoryMovement::TYPE_RECEIPT,
                    'source_type' => GoodsReceipt::class,
                    'source_id' => $receipt->id,
                    'quantity_change' => $receivedQuantity,
                    'unit_cost' => $line['unit_cost'] ?? $orderLine->unit_cost,
                    'moved_at' => $receipt->received_at,
                    'notes' => 'Received against '.$order->purchase_order_number,
                ]);
            }

            $order->refresh();
            $order->load('lines');

            $allReceived = $order->lines->every(fn ($line) => (float) $line->received_quantity >= (float) $line->ordered_quantity);
            $anyReceived = $order->lines->contains(fn ($line) => (float) $line->received_quantity > 0);

            $order->update([
                'status' => $allReceived
                    ? PurchaseOrder::STATUS_RECEIVED
                    : ($anyReceived ? PurchaseOrder::STATUS_PARTIALLY_RECEIVED : $order->status),
                'received_at' => $allReceived ? ($attributes['received_at'] ?? now()) : $order->received_at,
            ]);

            return $this->syncPurchaseOrderBillingStatus($order->fresh());
        });
    }

    public function issueInventoryItem(InventoryItem $item, float $quantity, array $attributes = []): InventoryMovement
    {
        return DB::transaction(function () use ($item, $quantity, $attributes) {
            if ($quantity <= 0) {
                throw new InvalidArgumentException('Inventory issues require a positive quantity.');
            }

            $currentQuantity = (float) $item->current_quantity;

            if ($currentQuantity < $quantity) {
                throw new InvalidArgumentException('Insufficient stock for '.$item->name.'.');
            }

            $item->update([
                'current_quantity' => $currentQuantity - $quantity,
            ]);

            return InventoryMovement::query()->create([
                'company_id' => $item->company_id,
                'inventory_item_id' => $item->id,
                'movement_type' => InventoryMovement::TYPE_ISSUE,
                'source_type' => $attributes['source_type'] ?? null,
                'source_id' => $attributes['source_id'] ?? null,
                'quantity_change' => -1 * $quantity,
                'unit_cost' => $attributes['unit_cost'] ?? $item->unit_cost,
                'moved_at' => $attributes['moved_at'] ?? now(),
                'notes' => $attributes['notes'] ?? null,
            ]);
        });
    }

    public function restockInventoryItem(InventoryItem $item, float $quantity, array $attributes = []): InventoryMovement
    {
        return DB::transaction(function () use ($item, $quantity, $attributes) {
            if ($quantity <= 0) {
                throw new InvalidArgumentException('Inventory restock requires a positive quantity.');
            }

            $item->update([
                'current_quantity' => (float) $item->current_quantity + $quantity,
            ]);

            return InventoryMovement::query()->create([
                'company_id' => $item->company_id,
                'inventory_item_id' => $item->id,
                'movement_type' => InventoryMovement::TYPE_ADJUSTMENT,
                'source_type' => $attributes['source_type'] ?? null,
                'source_id' => $attributes['source_id'] ?? null,
                'quantity_change' => $quantity,
                'unit_cost' => $attributes['unit_cost'] ?? $item->unit_cost,
                'moved_at' => $attributes['moved_at'] ?? now(),
                'notes' => $attributes['notes'] ?? null,
            ]);
        });
    }

    public function recordWastage(InventoryItem $item, float $quantity, array $attributes = []): InventoryMovement
    {
        return DB::transaction(function () use ($item, $quantity, $attributes) {
            if ($quantity <= 0) {
                throw new InvalidArgumentException('Wastage requires a positive quantity.');
            }

            $currentQuantity = (float) $item->current_quantity;

            if ($currentQuantity < $quantity) {
                throw new InvalidArgumentException('Insufficient stock to record wastage for '.$item->name.'.');
            }

            $item->update([
                'current_quantity' => $currentQuantity - $quantity,
            ]);

            return InventoryMovement::query()->create([
                'company_id' => $item->company_id,
                'inventory_item_id' => $item->id,
                'movement_type' => InventoryMovement::TYPE_WASTAGE,
                'source_type' => $attributes['source_type'] ?? null,
                'source_id' => $attributes['source_id'] ?? null,
                'quantity_change' => -1 * $quantity,
                'unit_cost' => $attributes['unit_cost'] ?? $item->unit_cost,
                'moved_at' => $attributes['moved_at'] ?? now(),
                'notes' => $attributes['notes'] ?? null,
            ]);
        });
    }

    public function syncPurchaseOrderBillingStatus(PurchaseOrder $order): PurchaseOrder
    {
        $order->loadMissing('lines');

        $matchStatus = PurchaseOrder::MATCH_STATUS_UNMATCHED;

        if ($order->lines->isNotEmpty()) {
            $allMatched = $order->lines->every(fn (PurchaseOrderLine $line) => (float) $line->billed_quantity >= (float) $line->received_quantity && (float) $line->received_quantity > 0);
            $anyMatched = $order->lines->contains(fn (PurchaseOrderLine $line) => (float) $line->billed_quantity > 0);
            $anyToleranceVariance = $order->lines->contains(function (PurchaseOrderLine $line) use ($order) {
                $expectedQuantity = $line->received_quantity > 0 ? (float) $line->received_quantity : (float) $line->ordered_quantity;

                if ($expectedQuantity <= 0 || (float) $line->billed_quantity <= 0) {
                    return false;
                }

                $variance = abs((float) $line->billed_quantity - $expectedQuantity);
                $percent = ($variance / $expectedQuantity) * 100;

                return $percent > 0 && $percent <= (float) $order->quantity_tolerance_percent;
            });

            $matchStatus = $allMatched
                ? ($anyToleranceVariance ? PurchaseOrder::MATCH_STATUS_WITHIN_TOLERANCE : PurchaseOrder::MATCH_STATUS_MATCHED)
                : ($anyMatched ? PurchaseOrder::MATCH_STATUS_PARTIAL : PurchaseOrder::MATCH_STATUS_UNMATCHED);
        }

        $order->update(['match_status' => $matchStatus]);

        return $order->fresh(['supplier', 'approver', 'approvals.approver', 'lines.item', 'receipts.lines']);
    }
}