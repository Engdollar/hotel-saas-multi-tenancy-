<?php

namespace App\Domain\Hotel\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomMove extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $table = 'hotel_room_moves';

    protected $fillable = [
        'company_id',
        'reservation_id',
        'from_room_id',
        'to_room_id',
        'moved_by_user_id',
        'reason',
        'moved_at',
    ];

    protected function casts(): array
    {
        return [
            'moved_at' => 'datetime',
        ];
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function fromRoom()
    {
        return $this->belongsTo(Room::class, 'from_room_id');
    }

    public function toRoom()
    {
        return $this->belongsTo(Room::class, 'to_room_id');
    }

    public function movedBy()
    {
        return $this->belongsTo(User::class, 'moved_by_user_id');
    }
}