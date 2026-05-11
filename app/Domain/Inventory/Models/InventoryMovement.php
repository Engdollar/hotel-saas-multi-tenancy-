<?php

namespace App\Domain\Inventory\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    use BelongsToCompany;
    use HasFactory;

    public const TYPE_RECEIPT = 'receipt';
    public const TYPE_ISSUE = 'issue';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_WASTAGE = 'wastage';

    protected $table = 'inventory_movements';

    protected $fillable = [
        'company_id',
        'inventory_item_id',
        'movement_type',
        'source_type',
        'source_id',
        'quantity_change',
        'unit_cost',
        'moved_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_change' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'moved_at' => 'datetime',
        ];
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}