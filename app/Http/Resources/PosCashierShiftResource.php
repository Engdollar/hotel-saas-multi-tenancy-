<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosCashierShiftResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'property_id' => $this->property_id,
            'user_id' => $this->user_id,
            'shift_number' => $this->shift_number,
            'status' => $this->status,
            'opening_cash_amount' => $this->opening_cash_amount,
            'closing_cash_amount' => $this->closing_cash_amount,
            'expected_cash_amount' => $this->expected_cash_amount,
            'variance_amount' => $this->variance_amount,
            'opened_at' => $this->opened_at,
            'closed_at' => $this->closed_at,
            'notes' => $this->notes,
            'orders' => PosOrderResource::collection($this->whenLoaded('orders')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}