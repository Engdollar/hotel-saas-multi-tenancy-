<?php

namespace App\Domain\Hotel\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomType extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $table = 'hotel_room_types';

    protected $fillable = [
        'company_id',
        'property_id',
        'name',
        'code',
        'base_rate',
        'capacity_adults',
        'capacity_children',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'base_rate' => 'decimal:2',
        ];
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
}