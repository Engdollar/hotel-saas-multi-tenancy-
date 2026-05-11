<?php

namespace App\Domain\Hotel\Services;

use App\Domain\Hotel\Models\HousekeepingTask;
use App\Domain\Hotel\Models\Reservation;
use App\Domain\Hotel\Models\RoomInspection;

class HousekeepingService
{
    public function createCheckoutCleaningTask(Reservation $reservation, ?string $notes = null): HousekeepingTask
    {
        return HousekeepingTask::query()->create([
            'company_id' => $reservation->company_id,
            'property_id' => $reservation->property_id,
            'room_id' => $reservation->room_id,
            'reservation_id' => $reservation->id,
            'task_type' => HousekeepingTask::TYPE_CHECKOUT_CLEANING,
            'status' => HousekeepingTask::STATUS_PENDING,
            'priority' => 'high',
            'scheduled_for' => now(),
            'notes' => $notes,
        ]);
    }

    public function updateTask(HousekeepingTask $task, array $attributes): HousekeepingTask
    {
        if (($attributes['assigned_to_user_id'] ?? null) && ! $task->assigned_at) {
            $attributes['assigned_at'] = now();
        }

        if (($attributes['status'] ?? null) === HousekeepingTask::STATUS_IN_PROGRESS && ! $task->started_at) {
            $attributes['started_at'] = now();
        }

        if (($attributes['minibar_status'] ?? null) === HousekeepingTask::MINIBAR_STATUS_RESTOCKED && ! $task->minibar_restocked_at) {
            $attributes['minibar_restocked_at'] = now();
        }

        if (in_array(($attributes['status'] ?? null), [HousekeepingTask::STATUS_COMPLETED, HousekeepingTask::STATUS_INSPECTED], true)) {
            $attributes['completed_at'] = $attributes['completed_at'] ?? now();
        }

        $task->update($attributes);

        $task = $task->fresh();

        if ($task->inspection_status && $task->inspected_by_user_id) {
            RoomInspection::query()->updateOrCreate(
                ['housekeeping_task_id' => $task->id],
                [
                    'company_id' => $task->company_id,
                    'property_id' => $task->property_id,
                    'room_id' => $task->room_id,
                    'inspected_by_user_id' => $task->inspected_by_user_id,
                    'inspection_type' => 'room_ready',
                    'status' => $task->inspection_status,
                    'checklist' => [
                        'linen_status' => $task->linen_status,
                        'minibar_status' => $task->minibar_status,
                    ],
                    'notes' => $task->inspection_notes,
                    'inspected_at' => now(),
                ],
            );
        }

        if (in_array($task->status, [HousekeepingTask::STATUS_COMPLETED, HousekeepingTask::STATUS_INSPECTED], true)) {
            $task->room?->update([
                'cleaning_status' => $task->inspection_status === HousekeepingTask::INSPECTION_STATUS_FAILED ? 'dirty' : 'clean',
                'status' => $task->inspection_status === HousekeepingTask::INSPECTION_STATUS_FAILED ? 'dirty' : 'available',
            ]);
        }

        return $task->fresh(['property', 'room', 'reservation', 'assignee', 'inspector', 'inspections']);
    }
}