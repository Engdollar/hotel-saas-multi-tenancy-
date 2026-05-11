<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'guest_profile_id' => $this->guest_profile_id,
            'folio_id' => $this->folio_id,
            'status' => $this->status,
            'currency_code' => $this->currency_code,
            'issue_date' => $this->issue_date,
            'due_date' => $this->due_date,
            'subtotal_amount' => $this->subtotal_amount,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'balance_amount' => $this->balance_amount,
            'notes' => $this->notes,
            'lines' => $this->whenLoaded('lines', fn () => $this->lines->map(fn ($line) => [
                'id' => $line->id,
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'tax_amount' => $line->tax_amount,
                'total_amount' => $line->total_amount,
            ])),
            'payments' => $this->whenLoaded('payments', fn () => $this->payments->map(fn ($payment) => [
                'id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'amount' => $payment->amount,
                'paid_at' => $payment->paid_at,
                'payment_method' => $payment->payment_method,
            ])),
            'refunds' => $this->whenLoaded('refunds', fn () => $this->refunds->map(fn ($refund) => [
                'id' => $refund->id,
                'refund_number' => $refund->refund_number,
                'amount' => $refund->amount,
                'refunded_at' => $refund->refunded_at,
                'reason' => $refund->reason,
            ])),
        ];
    }
}