<?php

namespace App\Domain\Accounting\Models;

use App\Domain\Inventory\Models\InventoryItem;
use App\Domain\Inventory\Models\PurchaseOrderLine;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierBillLine extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $table = 'accounting_supplier_bill_lines';

    protected $fillable = [
        'company_id',
        'supplier_bill_id',
        'purchase_order_line_id',
        'inventory_item_id',
        'ledger_account_id',
        'description',
        'quantity',
        'unit_cost',
        'tax_amount',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function supplierBill()
    {
        return $this->belongsTo(SupplierBill::class);
    }

    public function ledgerAccount()
    {
        return $this->belongsTo(LedgerAccount::class);
    }

    public function purchaseOrderLine()
    {
        return $this->belongsTo(PurchaseOrderLine::class, 'purchase_order_line_id');
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}