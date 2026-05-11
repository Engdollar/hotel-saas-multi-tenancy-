<?php

namespace App\Domain\Hotel\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PosOrder extends Model
{
    use BelongsToCompany;
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_PAID = 'paid';
    public const STATUS_VOID = 'void';

    protected $table = 'hotel_pos_orders';

    protected $fillable = [
        'company_id',
        'property_id',
        'cashier_shift_id',
        'reservation_id',
        'folio_id',
        'order_number',
        'status',
        'payment_method',
        'service_location',
        'charge_to_room',
        'posted_to_folio_at',
        'paid_at',
        'voided_at',
        'void_reason',
        'subtotal_amount',
        'tax_amount',
        'total_amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'charge_to_room' => 'boolean',
            'posted_to_folio_at' => 'datetime',
            'paid_at' => 'datetime',
            'voided_at' => 'datetime',
            'subtotal_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $order): void {
            if (! $order->order_number) {
                $order->order_number = 'POS-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
            }
        });
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function shift()
    {
        return $this->belongsTo(PosCashierShift::class, 'cashier_shift_id');
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function folio()
    {
        return $this->belongsTo(Folio::class);
    }

    public function lines()
    {
        return $this->hasMany(PosOrderLine::class, 'pos_order_id')->orderBy('id');
    }
}