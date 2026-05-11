<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'property_id' => $this->property_id,
            'room_id' => $this->room_id,
            'reported_by_user_id' => $this->reported_by_user_id,
            'assigned_to_user_id' => $this->assigned_to_user_id,
            'assigned_at' => $this->assigned_at,
            'title' => $this->title,
            'description' => $this->description,
            'maintenance_category' => $this->maintenance_category,
            'priority' => $this->priority,
            'is_preventive' => $this->is_preventive,
            'preventive_maintenance_schedule_id' => $this->preventive_maintenance_schedule_id,
            'status' => $this->status,
            'reported_at' => $this->reported_at,
            'scheduled_for' => $this->scheduled_for,
            'work_started_at' => $this->work_started_at,
            'work_completed_at' => $this->work_completed_at,
            'resolved_at' => $this->resolved_at,
            'technician_notes' => $this->technician_notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}