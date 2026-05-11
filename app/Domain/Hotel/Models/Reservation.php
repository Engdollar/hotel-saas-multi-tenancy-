<?php

namespace App\Domain\Hotel\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Reservation extends Model
{
    use BelongsToCompany;
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CHECKED_IN = 'checked_in';
    public const STATUS_CHECKED_OUT = 'checked_out';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_NO_SHOW = 'no_show';

    public const PRE_ARRIVAL_STATUS_PENDING = 'pending';
    public const PRE_ARRIVAL_STATUS_IN_PROGRESS = 'in_progress';
    public const PRE_ARRIVAL_STATUS_COMPLETED = 'completed';

    protected $table = 'hotel_reservations';

    protected $fillable = [
        'company_id',
        'property_id',
        'room_id',
        'guest_profile_id',
        'reservation_number',
        'booking_source',
        'currency_code',
        'status',
        'check_in_date',
        'check_out_date',
        'actual_check_in_at',
        'actual_check_out_at',
        'checked_in_by_user_id',
        'checked_out_by_user_id',
        'check_in_signature_name',
        'check_in_signature_path',
        'signed_registration_at',
        'id_verified_at',
        'id_verified_by_user_id',
        'pre_arrival_status',
        'pre_arrival_submitted_at',
        'pre_arrival_completed_at',
        'expected_arrival_time',
        'registration_channel',
        'emergency_contact_name',
        'emergency_contact_phone',
        'compliance_notes',
        'adult_count',
        'child_count',
        'night_count',
        'rate_amount',
        'tax_amount',
        'total_amount',
        'special_requests',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'check_in_date' => 'date',
            'check_out_date' => 'date',
            'actual_check_in_at' => 'datetime',
            'actual_check_out_at' => 'datetime',
            'signed_registration_at' => 'datetime',
            'id_verified_at' => 'datetime',
            'pre_arrival_submitted_at' => 'datetime',
            'pre_arrival_completed_at' => 'datetime',
            'expected_arrival_time' => 'datetime:H:i',
            'rate_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $reservation): void {
            if (! $reservation->reservation_number) {
                $reservation->reservation_number = 'RSV-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
            }

            if (! $reservation->night_count && $reservation->check_in_date && $reservation->check_out_date) {
                $reservation->night_count = max(1, $reservation->check_in_date->diffInDays($reservation->check_out_date));
            }
        });
    }

    public static function activeStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_CHECKED_IN,
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

    public function guestProfile()
    {
        return $this->belongsTo(GuestProfile::class);
    }

    public function roomMoves()
    {
        return $this->hasMany(RoomMove::class)->latest('moved_at');
    }

    public function identityDocuments()
    {
        return $this->hasMany(GuestIdentityDocument::class)->latest()->with('extractionRequests');
    }

    public function visitors()
    {
        return $this->hasMany(ReservationVisitor::class)->latest('checked_in_at');
    }

    public function checkInVerifier()
    {
        return $this->belongsTo(User::class, 'id_verified_by_user_id');
    }
}