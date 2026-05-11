<?php

namespace App\Domain\Hotel\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservationVisitor extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $table = 'hotel_reservation_visitors';

    protected $fillable = [
        'company_id',
        'reservation_id',
        'full_name',
        'relationship_to_guest',
        'identification_number',
        'phone',
        'checked_in_at',
        'checked_out_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
        ];
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }
}