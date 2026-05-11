<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FolioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'folio_number' => $this->folio_number,
            'reservation_id' => $this->reservation_id,
            'guest_profile_id' => $this->guest_profile_id,
            'status' => $this->status,
            'currency_code' => $this->currency_code,
            'subtotal_amount' => $this->subtotal_amount,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'balance_amount' => $this->balance_amount,
            'opened_at' => $this->opened_at,
            'closed_at' => $this->closed_at,
            'lines' => $this->whenLoaded('lines', fn () => $this->lines->map(fn ($line) => [
                'id' => $line->id,
                'description' => $line->description,
                'line_type' => $line->line_type,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'tax_amount' => $line->tax_amount,
                'total_amount' => $line->total_amount,
                'service_date' => $line->service_date,
            ])),
        ];
    }
}