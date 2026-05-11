<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomInspectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'property_id' => $this->property_id,
            'room_id' => $this->room_id,
            'housekeeping_task_id' => $this->housekeeping_task_id,
            'inspected_by_user_id' => $this->inspected_by_user_id,
            'inspection_type' => $this->inspection_type,
            'status' => $this->status,
            'checklist' => $this->checklist,
            'notes' => $this->notes,
            'inspected_at' => $this->inspected_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}