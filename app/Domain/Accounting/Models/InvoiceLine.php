<?php

namespace App\Domain\Accounting\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceLine extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $table = 'accounting_invoice_lines';

    protected $fillable = [
        'company_id',
        'invoice_id',
        'ledger_account_id',
        'description',
        'quantity',
        'unit_price',
        'tax_amount',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function ledgerAccount()
    {
        return $this->belongsTo(LedgerAccount::class);
    }
}