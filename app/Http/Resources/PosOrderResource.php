<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'property_id' => $this->property_id,
            'cashier_shift_id' => $this->cashier_shift_id,
            'reservation_id' => $this->reservation_id,
            'folio_id' => $this->folio_id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'service_location' => $this->service_location,
            'charge_to_room' => $this->charge_to_room,
            'posted_to_folio_at' => $this->posted_to_folio_at,
            'paid_at' => $this->paid_at,
            'voided_at' => $this->voided_at,
            'void_reason' => $this->void_reason,
            'subtotal_amount' => $this->subtotal_amount,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'notes' => $this->notes,
            'lines' => $this->whenLoaded('lines', fn () => $this->lines->map(fn ($line) => [
                'id' => $line->id,
                'inventory_item_id' => $line->inventory_item_id,
                'item_name' => $line->item_name,
                'category' => $line->category,
                'modifiers' => $line->modifiers,
                'modifier_total_amount' => $line->modifier_total_amount,
                'kitchen_station' => $line->kitchen_station,
                'kitchen_status' => $line->kitchen_status,
                'sent_to_kitchen_at' => $line->sent_to_kitchen_at,
                'kitchen_completed_at' => $line->kitchen_completed_at,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'tax_amount' => $line->tax_amount,
                'total_amount' => $line->total_amount,
                'wasted_quantity' => $line->wasted_quantity,
                'wastage_reason' => $line->wastage_reason,
            ])),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}