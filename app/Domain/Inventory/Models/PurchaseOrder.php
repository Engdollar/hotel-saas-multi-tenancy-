<?php

namespace App\Domain\Inventory\Models;

use App\Domain\Accounting\Models\Supplier;
use App\Domain\Hotel\Models\Property;
use App\Models\Concerns\BelongsToCompany;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PurchaseOrder extends Model
{
    use BelongsToCompany;
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PARTIALLY_RECEIVED = 'partially_received';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REJECTED = 'rejected';

    public const MATCH_STATUS_UNMATCHED = 'unmatched';
    public const MATCH_STATUS_PARTIAL = 'partial';
    public const MATCH_STATUS_MATCHED = 'matched';
    public const MATCH_STATUS_WITHIN_TOLERANCE = 'within_tolerance';
    public const MATCH_STATUS_EXCEPTION = 'exception';

    protected $table = 'procurement_purchase_orders';

    protected $fillable = [
        'company_id',
        'property_id',
        'supplier_id',
        'approved_by_user_id',
        'purchase_order_number',
        'status',
        'match_status',
        'currency_code',
        'order_date',
        'expected_delivery_date',
        'approved_at',
        'received_at',
        'subtotal_amount',
        'tax_amount',
        'total_amount',
        'quantity_tolerance_percent',
        'amount_tolerance_percent',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_delivery_date' => 'date',
            'approved_at' => 'datetime',
            'received_at' => 'datetime',
            'subtotal_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'quantity_tolerance_percent' => 'decimal:2',
            'amount_tolerance_percent' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $order): void {
            if (! $order->purchase_order_number) {
                $order->purchase_order_number = 'PO-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
            }

            $order->order_date ??= now()->toDateString();
        });
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function lines()
    {
        return $this->hasMany(PurchaseOrderLine::class, 'purchase_order_id')->orderBy('id');
    }

    public function receipts()
    {
        return $this->hasMany(GoodsReceipt::class, 'purchase_order_id')->latest('received_at');
    }

    public function approvals()
    {
        return $this->hasMany(PurchaseOrderApproval::class, 'purchase_order_id')->orderBy('sequence_number');
    }
}