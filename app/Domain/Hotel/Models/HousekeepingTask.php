<?php

namespace App\Domain\Hotel\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HousekeepingTask extends Model
{
    use BelongsToCompany;
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_INSPECTED = 'inspected';

    public const TYPE_CHECKOUT_CLEANING = 'checkout_cleaning';
    public const TYPE_STAYOVER_CLEANING = 'stayover_cleaning';
    public const TYPE_INSPECTION = 'inspection';
    public const TYPE_TURNDOWN = 'turndown';

    public const LINEN_STATUS_NOT_REQUIRED = 'not_required';
    public const LINEN_STATUS_PENDING = 'pending';
    public const LINEN_STATUS_COMPLETED = 'completed';

    public const MINIBAR_STATUS_NOT_CHECKED = 'not_checked';
    public const MINIBAR_STATUS_PENDING = 'pending';
    public const MINIBAR_STATUS_RESTOCKED = 'restocked';

    public const INSPECTION_STATUS_PASSED = 'passed';
    public const INSPECTION_STATUS_FAILED = 'failed';

    protected $table = 'hotel_housekeeping_tasks';

    protected $fillable = [
        'company_id',
        'property_id',
        'room_id',
        'reservation_id',
        'assigned_to_user_id',
        'assigned_at',
        'task_type',
        'status',
        'priority',
        'linen_status',
        'linen_items_collected',
        'linen_items_delivered',
        'minibar_status',
        'minibar_restocked_at',
        'minibar_charge_amount',
        'inspection_status',
        'inspected_by_user_id',
        'inspection_notes',
        'scheduled_for',
        'started_at',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'linen_items_collected' => 'integer',
            'linen_items_delivered' => 'integer',
            'minibar_restocked_at' => 'datetime',
            'minibar_charge_amount' => 'decimal:2',
            'scheduled_for' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
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

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspected_by_user_id');
    }

    public function inspections()
    {
        return $this->hasMany(RoomInspection::class, 'housekeeping_task_id')->latest('inspected_at');
    }
}