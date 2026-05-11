<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HousekeepingTaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'property_id' => $this->property_id,
            'room_id' => $this->room_id,
            'reservation_id' => $this->reservation_id,
            'assigned_to_user_id' => $this->assigned_to_user_id,
            'assigned_at' => $this->assigned_at,
            'task_type' => $this->task_type,
            'status' => $this->status,
            'priority' => $this->priority,
            'linen_status' => $this->linen_status,
            'linen_items_collected' => $this->linen_items_collected,
            'linen_items_delivered' => $this->linen_items_delivered,
            'minibar_status' => $this->minibar_status,
            'minibar_restocked_at' => $this->minibar_restocked_at,
            'minibar_charge_amount' => $this->minibar_charge_amount,
            'inspection_status' => $this->inspection_status,
            'inspected_by_user_id' => $this->inspected_by_user_id,
            'inspection_notes' => $this->inspection_notes,
            'scheduled_for' => $this->scheduled_for,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'notes' => $this->notes,
            'inspections' => RoomInspectionResource::collection($this->whenLoaded('inspections')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}