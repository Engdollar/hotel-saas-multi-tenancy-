<?php

namespace App\Domain\Hotel\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreventiveMaintenanceSchedule extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $table = 'hotel_preventive_maintenance_schedules';

    protected $fillable = [
        'company_id',
        'property_id',
        'room_id',
        'assigned_to_user_id',
        'title',
        'description',
        'maintenance_category',
        'priority',
        'frequency_days',
        'last_generated_at',
        'next_due_at',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'last_generated_at' => 'datetime',
            'next_due_at' => 'datetime',
            'is_active' => 'boolean',
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

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function maintenanceRequests()
    {
        return $this->hasMany(MaintenanceRequest::class, 'preventive_maintenance_schedule_id')->latest();
    }
}