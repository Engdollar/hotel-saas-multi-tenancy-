<?php

namespace App\Domain\Inventory\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderLine extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $table = 'procurement_purchase_order_lines';

    protected $fillable = [
        'company_id',
        'purchase_order_id',
        'inventory_item_id',
        'description',
        'ordered_quantity',
        'received_quantity',
        'billed_quantity',
        'unit_cost',
        'tax_amount',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'ordered_quantity' => 'decimal:2',
            'received_quantity' => 'decimal:2',
            'billed_quantity' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}