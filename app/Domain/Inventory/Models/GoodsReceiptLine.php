<?php

namespace App\Domain\Inventory\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodsReceiptLine extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $table = 'procurement_goods_receipt_lines';

    protected $fillable = [
        'company_id',
        'goods_receipt_id',
        'purchase_order_line_id',
        'inventory_item_id',
        'received_quantity',
        'unit_cost',
    ];

    protected function casts(): array
    {
        return [
            'received_quantity' => 'decimal:2',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function receipt()
    {
        return $this->belongsTo(GoodsReceipt::class, 'goods_receipt_id');
    }

    public function purchaseOrderLine()
    {
        return $this->belongsTo(PurchaseOrderLine::class, 'purchase_order_line_id');
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}