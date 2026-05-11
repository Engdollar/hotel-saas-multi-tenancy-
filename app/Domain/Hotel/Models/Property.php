<?php

namespace App\Domain\Hotel\Models;

use App\Models\Company;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $table = 'hotel_properties';

    protected $fillable = [
        'company_id',
        'branch_code',
        'name',
        'property_type',
        'timezone',
        'currency_code',
        'check_in_time',
        'check_out_time',
        'status',
    ];

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function roomTypes()
    {
        return $this->hasMany(RoomType::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}