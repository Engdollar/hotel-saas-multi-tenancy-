<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PreventiveMaintenanceScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'property_id' => $this->property_id,
            'room_id' => $this->room_id,
            'assigned_to_user_id' => $this->assigned_to_user_id,
            'title' => $this->title,
            'description' => $this->description,
            'maintenance_category' => $this->maintenance_category,
            'priority' => $this->priority,
            'frequency_days' => $this->frequency_days,
            'last_generated_at' => $this->last_generated_at,
            'next_due_at' => $this->next_due_at,
            'is_active' => $this->is_active,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}