<?php

namespace App\Domain\Accounting\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankReconciliationLine extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $table = 'accounting_bank_reconciliation_lines';

    protected $fillable = [
        'company_id',
        'bank_reconciliation_id',
        'entry_type',
        'reference_type',
        'reference_id',
        'description',
        'transaction_date',
        'amount',
        'is_cleared',
        'cleared_at',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'amount' => 'decimal:2',
            'is_cleared' => 'boolean',
            'cleared_at' => 'datetime',
        ];
    }

    public function reconciliation()
    {
        return $this->belongsTo(BankReconciliation::class, 'bank_reconciliation_id');
    }
}