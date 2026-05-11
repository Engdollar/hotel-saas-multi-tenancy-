<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierBillResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bill_number' => $this->bill_number,
            'supplier_id' => $this->supplier_id,
            'purchase_order_id' => $this->purchase_order_id,
            'status' => $this->status,
            'match_status' => $this->match_status,
            'currency_code' => $this->currency_code,
            'bill_date' => $this->bill_date,
            'due_date' => $this->due_date,
            'subtotal_amount' => $this->subtotal_amount,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'balance_amount' => $this->balance_amount,
            'description' => $this->description,
            'lines' => $this->whenLoaded('lines', fn () => $this->lines->map(fn ($line) => [
                'id' => $line->id,
                'purchase_order_line_id' => $line->purchase_order_line_id,
                'inventory_item_id' => $line->inventory_item_id,
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unit_cost' => $line->unit_cost,
                'tax_amount' => $line->tax_amount,
                'total_amount' => $line->total_amount,
            ])),
            'payments' => $this->whenLoaded('payments', fn () => $this->payments->map(fn ($payment) => [
                'id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'payment_method' => $payment->payment_method,
                'amount' => $payment->amount,
                'paid_at' => $payment->paid_at,
            ])),
        ];
    }
}