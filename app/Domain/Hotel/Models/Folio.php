<?php

namespace App\Domain\Hotel\Models;

use App\Domain\Accounting\Models\Invoice;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Folio extends Model
{
    use BelongsToCompany;
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_INVOICED = 'invoiced';
    public const STATUS_SETTLED = 'settled';

    protected $table = 'hotel_folios';

    protected $fillable = [
        'company_id',
        'reservation_id',
        'guest_profile_id',
        'folio_number',
        'status',
        'currency_code',
        'subtotal_amount',
        'tax_amount',
        'total_amount',
        'balance_amount',
        'opened_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'balance_amount' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $folio): void {
            if (! $folio->folio_number) {
                $folio->folio_number = 'FOL-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
            }

            $folio->opened_at ??= now();
        });
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function guestProfile()
    {
        return $this->belongsTo(GuestProfile::class, 'guest_profile_id');
    }

    public function lines()
    {
        return $this->hasMany(FolioLine::class)->orderBy('id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}