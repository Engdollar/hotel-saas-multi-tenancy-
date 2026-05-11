<?php

namespace App\Domain\Hotel\Services;

use App\Domain\Accounting\Services\AccountingAccountService;
use App\Domain\Inventory\Models\InventoryItem;
use App\Domain\Inventory\Services\InventoryService;
use App\Domain\Hotel\Models\PosCashierShift;
use App\Domain\Hotel\Models\PosOrder;
use App\Domain\Hotel\Models\PosOrderLine;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PosService
{
    public function __construct(
        protected FolioService $folioService,
        protected AccountingAccountService $accountingAccountService,
        protected InventoryService $inventoryService,
    ) {
    }

    public function openShift(array $attributes): PosCashierShift
    {
        return PosCashierShift::query()->create([
            ...$attributes,
            'status' => $attributes['status'] ?? PosCashierShift::STATUS_OPEN,
            'opening_cash_amount' => $attributes['opening_cash_amount'] ?? 0,
            'expected_cash_amount' => $attributes['opening_cash_amount'] ?? 0,
            'variance_amount' => 0,
            'opened_at' => $attributes['opened_at'] ?? now(),
        ]);
    }

    public function closeShift(PosCashierShift $shift, array $attributes): PosCashierShift
    {
        $expectedCash = (float) $shift->opening_cash_amount + (float) $shift->orders()
            ->where('status', PosOrder::STATUS_PAID)
            ->where('payment_method', 'cash')
            ->sum('total_amount');

        $closingCash = (float) ($attributes['closing_cash_amount'] ?? $shift->closing_cash_amount);

        $shift->update([
            'status' => PosCashierShift::STATUS_CLOSED,
            'closing_cash_amount' => $closingCash,
            'expected_cash_amount' => $expectedCash,
            'variance_amount' => $closingCash - $expectedCash,
            'closed_at' => $attributes['closed_at'] ?? now(),
            'notes' => $attributes['notes'] ?? $shift->notes,
        ]);

        return $shift->fresh(['cashier', 'orders.lines']);
    }

    public function createOrder(array $attributes): PosOrder
    {
        return DB::transaction(function () use ($attributes) {
            $lineItems = $attributes['lines'] ?? [];
            unset($attributes['lines']);

            if ($lineItems === []) {
                throw new InvalidArgumentException('POS orders require at least one line item.');
            }

            $subtotal = collect($lineItems)->sum(fn (array $line) => ((float) $line['quantity'] * (float) $line['unit_price']) + $this->modifierTotal($line));
            $tax = collect($lineItems)->sum(fn (array $line) => (float) ($line['tax_amount'] ?? 0));
            $total = collect($lineItems)->sum(fn (array $line) => (float) ($line['total_amount'] ?? ((((float) $line['quantity'] * (float) $line['unit_price']) + $this->modifierTotal($line)) + (float) ($line['tax_amount'] ?? 0))));

            $order = PosOrder::query()->create([
                ...$attributes,
                'status' => $attributes['status'] ?? PosOrder::STATUS_PAID,
                'subtotal_amount' => $subtotal,
                'tax_amount' => $tax,
                'total_amount' => $total,
                'paid_at' => ($attributes['status'] ?? PosOrder::STATUS_PAID) === PosOrder::STATUS_PAID ? ($attributes['paid_at'] ?? now()) : null,
            ]);

            foreach ($lineItems as $line) {
                $modifierTotal = $this->modifierTotal($line);
                $amount = (float) ($line['total_amount'] ?? ((((float) $line['quantity'] * (float) $line['unit_price']) + $modifierTotal) + (float) ($line['tax_amount'] ?? 0)));
                $inventoryItem = isset($line['inventory_item_id'])
                    ? InventoryItem::query()->findOrFail($line['inventory_item_id'])
                    : null;
                $itemName = $line['item_name'] ?? $inventoryItem?->name;

                if (! $itemName) {
                    throw new InvalidArgumentException('POS order lines require either an item name or inventory item.');
                }

                $orderLine = $order->lines()->create([
                    'company_id' => $order->company_id,
                    'ledger_account_id' => $line['ledger_account_id'] ?? $this->accountingAccountService->foodAndBeverageRevenue($order->currency_code ?? 'USD')->id,
                    'inventory_item_id' => $inventoryItem?->id,
                    'item_name' => $itemName,
                    'category' => $line['category'] ?? $inventoryItem?->category,
                    'modifiers' => $line['modifiers'] ?? null,
                    'modifier_total_amount' => $modifierTotal,
                    'kitchen_station' => $line['kitchen_station'] ?? null,
                    'kitchen_status' => PosOrderLine::KITCHEN_STATUS_PENDING,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'tax_amount' => $line['tax_amount'] ?? 0,
                    'total_amount' => $amount,
                ]);

                if ($inventoryItem && $order->status === PosOrder::STATUS_PAID) {
                    $this->inventoryService->issueInventoryItem($inventoryItem, (float) $line['quantity'], [
                        'source_type' => PosOrder::class,
                        'source_id' => $order->id,
                        'unit_cost' => $inventoryItem->unit_cost,
                        'moved_at' => $order->paid_at ?? now(),
                        'notes' => 'Consumed by POS order '.$order->order_number.' line '.$orderLine->id,
                    ]);
                }
            }

            if ($order->charge_to_room) {
                $this->postOrderToFolio($order->fresh(['lines']));
            }

            return $order->fresh(['lines', 'folio', 'reservation', 'shift']);
        });
    }

    public function postOrderToFolio(PosOrder $order): PosOrder
    {
        if ($order->posted_to_folio_at) {
            return $order->fresh(['lines', 'folio', 'reservation', 'shift']);
        }

        $folio = $order->folio;

        if (! $folio && $order->reservation_id) {
            $folio = $this->folioService->openForReservation($order->reservation()->firstOrFail());
            $order->update(['folio_id' => $folio->id]);
        }

        if (! $folio) {
            throw new InvalidArgumentException('Room-charge POS orders require a folio or reservation.');
        }

        foreach ($order->lines as $line) {
            $this->folioService->addCharge($folio, [
                'ledger_account_id' => $line->ledger_account_id,
                'line_type' => 'pos_charge',
                'description' => $line->item_name,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'tax_amount' => $line->tax_amount,
                'total_amount' => $line->total_amount,
                'service_date' => now()->toDateString(),
            ]);
        }

        $order->update(['posted_to_folio_at' => now()]);

        return $order->fresh(['lines', 'folio.lines', 'reservation', 'shift']);
    }

    public function sendToKitchen(PosOrder $order, array $attributes = []): PosOrder
    {
        $lineIds = $attributes['line_ids'] ?? null;

        $query = $order->lines()->whereNotNull('kitchen_station');

        if (is_array($lineIds) && $lineIds !== []) {
            $query->whereIn('id', $lineIds);
        }

        $query->where('kitchen_status', PosOrderLine::KITCHEN_STATUS_PENDING)->update([
            'kitchen_status' => PosOrderLine::KITCHEN_STATUS_FIRED,
            'sent_to_kitchen_at' => now(),
        ]);

        return $order->fresh(['lines', 'folio', 'reservation', 'shift']);
    }

    public function markKitchenReady(PosOrder $order, array $attributes = []): PosOrder
    {
        $lineIds = $attributes['line_ids'] ?? null;

        $query = $order->lines()->whereNotNull('kitchen_station');

        if (is_array($lineIds) && $lineIds !== []) {
            $query->whereIn('id', $lineIds);
        }

        $query->where('kitchen_status', PosOrderLine::KITCHEN_STATUS_FIRED)->update([
            'kitchen_status' => PosOrderLine::KITCHEN_STATUS_READY,
            'kitchen_completed_at' => now(),
        ]);

        return $order->fresh(['lines', 'folio', 'reservation', 'shift']);
    }

    public function voidOrder(PosOrder $order, array $attributes): PosOrder
    {
        return DB::transaction(function () use ($order, $attributes) {
            if ($order->status === PosOrder::STATUS_VOID) {
                return $order->fresh(['lines', 'folio', 'reservation', 'shift']);
            }

            if ($order->posted_to_folio_at) {
                throw new InvalidArgumentException('Posted room-charge orders cannot be voided automatically.');
            }

            $disposition = $attributes['inventory_disposition'] ?? 'waste';

            foreach ($order->lines as $line) {
                if (! $line->inventory_item_id || $disposition !== 'restock') {
                    $line->update(['kitchen_status' => PosOrderLine::KITCHEN_STATUS_VOIDED]);
                    continue;
                }

                $item = InventoryItem::query()->findOrFail($line->inventory_item_id);

                $this->inventoryService->restockInventoryItem($item, (float) $line->quantity, [
                    'source_type' => PosOrder::class,
                    'source_id' => $order->id,
                    'unit_cost' => $item->unit_cost,
                    'moved_at' => now(),
                    'notes' => 'Restocked from voided POS order '.$order->order_number,
                ]);

                $line->update(['kitchen_status' => PosOrderLine::KITCHEN_STATUS_VOIDED]);
            }

            $order->update([
                'status' => PosOrder::STATUS_VOID,
                'voided_at' => now(),
                'void_reason' => $attributes['reason'],
            ]);

            return $order->fresh(['lines', 'folio', 'reservation', 'shift']);
        });
    }

    public function recordWastage(PosOrder $order, array $attributes): PosOrder
    {
        return DB::transaction(function () use ($order, $attributes) {
            $line = $order->lines()->whereKey($attributes['pos_order_line_id'])->firstOrFail();

            if (! $line->inventory_item_id) {
                throw new InvalidArgumentException('Only inventory-backed POS lines can record wastage.');
            }

            $item = InventoryItem::query()->findOrFail($line->inventory_item_id);
            $wastedQuantity = (float) $attributes['wasted_quantity'];

            $this->inventoryService->recordWastage($item, $wastedQuantity, [
                'source_type' => PosOrder::class,
                'source_id' => $order->id,
                'unit_cost' => $item->unit_cost,
                'moved_at' => now(),
                'notes' => $attributes['reason'] ?? 'POS wastage adjustment for '.$order->order_number,
            ]);

            $line->update([
                'wasted_quantity' => (float) $line->wasted_quantity + $wastedQuantity,
                'wastage_reason' => $attributes['reason'] ?? $line->wastage_reason,
            ]);

            return $order->fresh(['lines', 'folio', 'reservation', 'shift']);
        });
    }

    protected function modifierTotal(array $line): float
    {
        return collect($line['modifiers'] ?? [])->sum(fn (array $modifier) => ((float) ($modifier['quantity'] ?? 1)) * (float) ($modifier['price'] ?? 0));
    }
}