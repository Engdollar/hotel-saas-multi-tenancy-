<?php

namespace App\Domain\Inventory\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GoodsReceipt extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $table = 'procurement_goods_receipts';

    protected $fillable = [
        'company_id',
        'purchase_order_id',
        'receipt_number',
        'received_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $receipt): void {
            if (! $receipt->receipt_number) {
                $receipt->receipt_number = 'GR-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
            }

            $receipt->received_at ??= now();
        });
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function lines()
    {
        return $this->hasMany(GoodsReceiptLine::class, 'goods_receipt_id')->orderBy('id');
    }
}