<?php

namespace App\Domain\Hotel\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use BelongsToCompany;
    use HasFactory;

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_OCCUPIED = 'occupied';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_DIRTY = 'dirty';
    public const STATUS_OUT_OF_ORDER = 'out_of_order';

    protected $table = 'hotel_rooms';

    protected $fillable = [
        'company_id',
        'property_id',
        'room_type_id',
        'floor_label',
        'room_number',
        'status',
        'cleaning_status',
        'is_smoking_allowed',
    ];

    protected function casts(): array
    {
        return [
            'is_smoking_allowed' => 'boolean',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_AVAILABLE,
            self::STATUS_OCCUPIED,
            self::STATUS_MAINTENANCE,
            self::STATUS_DIRTY,
            self::STATUS_OUT_OF_ORDER,
        ];
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}