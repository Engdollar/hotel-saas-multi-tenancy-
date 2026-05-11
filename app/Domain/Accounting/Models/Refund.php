<?php

namespace App\Domain\Accounting\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Refund extends Model
{
    use BelongsToCompany;
    use HasFactory;

    public const STATUS_POSTED = 'posted';
    public const STATUS_VOID = 'void';

    protected $table = 'accounting_refunds';

    protected $fillable = [
        'company_id',
        'invoice_id',
        'payment_id',
        'refund_number',
        'currency_code',
        'refunded_at',
        'amount',
        'reason',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'refunded_at' => 'datetime',
            'amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $refund): void {
            if (! $refund->refund_number) {
                $refund->refund_number = 'RFD-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
            }

            $refund->refunded_at ??= now();
        });
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}