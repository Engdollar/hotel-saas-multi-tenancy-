<?php

namespace App\Domain\Hotel\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceRequest extends Model
{
    use BelongsToCompany;
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    protected $table = 'hotel_maintenance_requests';

    protected $fillable = [
        'company_id',
        'property_id',
        'room_id',
        'reported_by_user_id',
        'assigned_to_user_id',
        'assigned_at',
        'work_started_at',
        'work_completed_at',
        'title',
        'description',
        'maintenance_category',
        'priority',
        'is_preventive',
        'preventive_maintenance_schedule_id',
        'status',
        'reported_at',
        'scheduled_for',
        'resolved_at',
        'technician_notes',
    ];

    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
            'assigned_at' => 'datetime',
            'work_started_at' => 'datetime',
            'work_completed_at' => 'datetime',
            'scheduled_for' => 'datetime',
            'resolved_at' => 'datetime',
            'is_preventive' => 'boolean',
        ];
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function preventiveSchedule()
    {
        return $this->belongsTo(PreventiveMaintenanceSchedule::class, 'preventive_maintenance_schedule_id');
    }
}