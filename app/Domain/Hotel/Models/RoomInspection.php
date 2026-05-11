<?php

namespace App\Domain\Hotel\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomInspection extends Model
{
    use BelongsToCompany;
    use HasFactory;

    public const STATUS_PASSED = 'passed';
    public const STATUS_FAILED = 'failed';

    protected $table = 'hotel_room_inspections';

    protected $fillable = [
        'company_id',
        'property_id',
        'room_id',
        'housekeeping_task_id',
        'inspected_by_user_id',
        'inspection_type',
        'status',
        'checklist',
        'notes',
        'inspected_at',
    ];

    protected function casts(): array
    {
        return [
            'checklist' => 'array',
            'inspected_at' => 'datetime',
        ];
    }

    public function task()
    {
        return $this->belongsTo(HousekeepingTask::class, 'housekeeping_task_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspected_by_user_id');
    }
}