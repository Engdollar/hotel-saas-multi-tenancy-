<?php

namespace App\Domain\Hotel\Services;

use App\Domain\Hotel\Models\MaintenanceRequest;
use App\Domain\Hotel\Models\PreventiveMaintenanceSchedule;
use App\Domain\Hotel\Models\Room;
use App\Models\User;
use Illuminate\Support\Carbon;

class MaintenanceService
{
    public function create(array $attributes): MaintenanceRequest
    {
        $request = MaintenanceRequest::query()->create([
            ...$attributes,
            'reported_at' => $attributes['reported_at'] ?? now(),
            'assigned_at' => ($attributes['assigned_to_user_id'] ?? null) ? ($attributes['assigned_at'] ?? now()) : null,
            'status' => $attributes['status'] ?? MaintenanceRequest::STATUS_OPEN,
        ]);

        if ($request->preventive_maintenance_schedule_id) {
            PreventiveMaintenanceSchedule::query()
                ->whereKey($request->preventive_maintenance_schedule_id)
                ->update(['last_generated_at' => now()]);
        }

        if ($request->room_id) {
            Room::query()->whereKey($request->room_id)->update(['status' => Room::STATUS_MAINTENANCE]);
        }

        return $request->fresh(['property', 'room', 'reporter', 'assignee', 'preventiveSchedule']);
    }

    public function update(MaintenanceRequest $request, array $attributes): MaintenanceRequest
    {
        if (($attributes['assigned_to_user_id'] ?? null) && ! $request->assigned_at) {
            $attributes['assigned_at'] = now();
        }

        if (($attributes['status'] ?? null) === MaintenanceRequest::STATUS_IN_PROGRESS && ! $request->work_started_at) {
            $attributes['work_started_at'] = now();
        }

        if (($attributes['status'] ?? null) === MaintenanceRequest::STATUS_COMPLETED) {
            $attributes['resolved_at'] = $attributes['resolved_at'] ?? now();
            $attributes['work_completed_at'] = $attributes['work_completed_at'] ?? now();
        }

        $request->update($attributes);

        $request = $request->fresh(['preventiveSchedule']);

        if ($request->status === MaintenanceRequest::STATUS_COMPLETED && $request->room_id) {
            Room::query()->whereKey($request->room_id)->update(['status' => Room::STATUS_DIRTY]);

            if ($request->preventiveSchedule && $request->preventiveSchedule->frequency_days > 0) {
                $resolvedAt = Carbon::parse($request->resolved_at ?? now());
                $request->preventiveSchedule->update([
                    'last_generated_at' => $resolvedAt,
                    'next_due_at' => $resolvedAt->copy()->addDays($request->preventiveSchedule->frequency_days),
                ]);
            }
        }

        return $request->fresh(['property', 'room', 'reporter', 'assignee', 'preventiveSchedule']);
    }

    public function generateFromSchedule(PreventiveMaintenanceSchedule $schedule, User $actor): MaintenanceRequest
    {
        return $this->create([
            'company_id' => $schedule->company_id,
            'property_id' => $schedule->property_id,
            'room_id' => $schedule->room_id,
            'reported_by_user_id' => $actor->id,
            'assigned_to_user_id' => $schedule->assigned_to_user_id,
            'title' => $schedule->title,
            'description' => $schedule->description,
            'maintenance_category' => $schedule->maintenance_category,
            'priority' => $schedule->priority,
            'status' => MaintenanceRequest::STATUS_OPEN,
            'scheduled_for' => $schedule->next_due_at ?? now(),
            'is_preventive' => true,
            'preventive_maintenance_schedule_id' => $schedule->id,
        ]);
    }
}