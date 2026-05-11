<?php

namespace App\Domain\Accounting\Models;

use App\Domain\Inventory\Models\PurchaseOrder;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SupplierBill extends Model
{
    use BelongsToCompany;
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PARTIALLY_PAID = 'partially_paid';
    public const STATUS_PAID = 'paid';
    public const STATUS_VOID = 'void';

    public const MATCH_STATUS_UNMATCHED = 'unmatched';
    public const MATCH_STATUS_PARTIAL = 'partial';
    public const MATCH_STATUS_MATCHED = 'matched';
    public const MATCH_STATUS_WITHIN_TOLERANCE = 'within_tolerance';
    public const MATCH_STATUS_EXCEPTION = 'exception';

    protected $table = 'accounting_supplier_bills';

    protected $fillable = [
        'company_id',
        'supplier_id',
        'purchase_order_id',
        'bill_number',
        'status',
        'match_status',
        'currency_code',
        'bill_date',
        'due_date',
        'subtotal_amount',
        'tax_amount',
        'total_amount',
        'balance_amount',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'bill_date' => 'date',
            'due_date' => 'date',
            'subtotal_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'balance_amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $bill): void {
            if (! $bill->bill_number) {
                $bill->bill_number = 'BIL-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
            }

            $bill->bill_date ??= now()->toDateString();
        });
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function lines()
    {
        return $this->hasMany(SupplierBillLine::class)->orderBy('id');
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class)->orderByDesc('paid_at');
    }
}