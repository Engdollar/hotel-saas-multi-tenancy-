<?php

namespace App\Domain\Inventory\Models;

use App\Domain\Accounting\Models\Supplier;
use App\Domain\Hotel\Models\Property;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $table = 'inventory_items';

    protected $fillable = [
        'company_id',
        'property_id',
        'preferred_supplier_id',
        'sku',
        'name',
        'category',
        'unit_of_measure',
        'current_quantity',
        'reorder_level',
        'par_level',
        'unit_cost',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'current_quantity' => 'decimal:2',
            'reorder_level' => 'decimal:2',
            'par_level' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function preferredSupplier()
    {
        return $this->belongsTo(Supplier::class, 'preferred_supplier_id');
    }

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class)->latest('moved_at');
    }
}