<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'property_id' => $this->property_id,
            'room_type_id' => $this->room_type_id,
            'floor_label' => $this->floor_label,
            'room_number' => $this->room_number,
            'status' => $this->status,
            'cleaning_status' => $this->cleaning_status,
            'is_smoking_allowed' => $this->is_smoking_allowed,
            'property' => new PropertyResource($this->whenLoaded('property')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}