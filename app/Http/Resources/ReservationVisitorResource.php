<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationVisitorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'relationship_to_guest' => $this->relationship_to_guest,
            'identification_number' => $this->identification_number,
            'phone' => $this->phone,
            'checked_in_at' => $this->checked_in_at,
            'checked_out_at' => $this->checked_out_at,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}