<?php

namespace App\Domain\Hotel\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PosCashierShift extends Model
{
    use BelongsToCompany;
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';

    protected $table = 'hotel_pos_cashier_shifts';

    protected $fillable = [
        'company_id',
        'property_id',
        'user_id',
        'shift_number',
        'status',
        'opening_cash_amount',
        'closing_cash_amount',
        'expected_cash_amount',
        'variance_amount',
        'opened_at',
        'closed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'opening_cash_amount' => 'decimal:2',
            'closing_cash_amount' => 'decimal:2',
            'expected_cash_amount' => 'decimal:2',
            'variance_amount' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $shift): void {
            if (! $shift->shift_number) {
                $shift->shift_number = 'SHIFT-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
            }

            $shift->opened_at ??= now();
        });
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function orders()
    {
        return $this->hasMany(PosOrder::class, 'cashier_shift_id')->latest();
    }
}