<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'property_id' => $this->property_id,
            'supplier_id' => $this->supplier_id,
            'purchase_order_number' => $this->purchase_order_number,
            'status' => $this->status,
            'match_status' => $this->match_status,
            'currency_code' => $this->currency_code,
            'order_date' => $this->order_date,
            'expected_delivery_date' => $this->expected_delivery_date,
            'approved_by_user_id' => $this->approved_by_user_id,
            'approved_at' => $this->approved_at,
            'received_at' => $this->received_at,
            'subtotal_amount' => $this->subtotal_amount,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'quantity_tolerance_percent' => $this->quantity_tolerance_percent,
            'amount_tolerance_percent' => $this->amount_tolerance_percent,
            'notes' => $this->notes,
            'approvals' => $this->whenLoaded('approvals', fn () => $this->approvals->map(fn ($approval) => [
                'id' => $approval->id,
                'sequence_number' => $approval->sequence_number,
                'approver_user_id' => $approval->approver_user_id,
                'status' => $approval->status,
                'acted_at' => $approval->acted_at,
                'notes' => $approval->notes,
            ])),
            'lines' => $this->whenLoaded('lines', fn () => $this->lines->map(fn ($line) => [
                'id' => $line->id,
                'inventory_item_id' => $line->inventory_item_id,
                'description' => $line->description,
                'ordered_quantity' => $line->ordered_quantity,
                'received_quantity' => $line->received_quantity,
                'billed_quantity' => $line->billed_quantity,
                'unit_cost' => $line->unit_cost,
                'tax_amount' => $line->tax_amount,
                'total_amount' => $line->total_amount,
            ])),
            'receipts' => $this->whenLoaded('receipts', fn () => $this->receipts->map(fn ($receipt) => [
                'id' => $receipt->id,
                'receipt_number' => $receipt->receipt_number,
                'received_at' => $receipt->received_at,
                'lines' => $receipt->lines->map(fn ($line) => [
                    'id' => $line->id,
                    'purchase_order_line_id' => $line->purchase_order_line_id,
                    'inventory_item_id' => $line->inventory_item_id,
                    'received_quantity' => $line->received_quantity,
                    'unit_cost' => $line->unit_cost,
                ]),
            ])),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}