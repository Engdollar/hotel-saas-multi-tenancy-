<?php

namespace App\Domain\Hotel\Models;

use App\Domain\Accounting\Models\LedgerAccount;
use App\Domain\Inventory\Models\InventoryItem;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosOrderLine extends Model
{
    use BelongsToCompany;
    use HasFactory;

    public const KITCHEN_STATUS_PENDING = 'pending';
    public const KITCHEN_STATUS_FIRED = 'fired';
    public const KITCHEN_STATUS_READY = 'ready';
    public const KITCHEN_STATUS_VOIDED = 'voided';

    protected $table = 'hotel_pos_order_lines';

    protected $fillable = [
        'company_id',
        'pos_order_id',
        'ledger_account_id',
        'inventory_item_id',
        'item_name',
        'category',
        'modifiers',
        'modifier_total_amount',
        'kitchen_station',
        'kitchen_status',
        'sent_to_kitchen_at',
        'kitchen_completed_at',
        'quantity',
        'unit_price',
        'tax_amount',
        'total_amount',
        'wasted_quantity',
        'wastage_reason',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'modifiers' => 'array',
            'modifier_total_amount' => 'decimal:2',
            'sent_to_kitchen_at' => 'datetime',
            'kitchen_completed_at' => 'datetime',
            'unit_price' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'wasted_quantity' => 'decimal:2',
        ];
    }

    public function order()
    {
        return $this->belongsTo(PosOrder::class, 'pos_order_id');
    }

    public function ledgerAccount()
    {
        return $this->belongsTo(LedgerAccount::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}