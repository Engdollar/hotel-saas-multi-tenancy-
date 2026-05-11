<?php

namespace App\Domain\Accounting\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SupplierPayment extends Model
{
    use BelongsToCompany;
    use HasFactory;

    public const STATUS_POSTED = 'posted';
    public const STATUS_VOID = 'void';

    protected $table = 'accounting_supplier_payments';

    protected $fillable = [
        'company_id',
        'supplier_bill_id',
        'payment_number',
        'payment_method',
        'currency_code',
        'paid_at',
        'amount',
        'reference',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $payment): void {
            if (! $payment->payment_number) {
                $payment->payment_number = 'SPY-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
            }

            $payment->paid_at ??= now();
        });
    }

    public function supplierBill()
    {
        return $this->belongsTo(SupplierBill::class);
    }
}