<?php

namespace App\Domain\Accounting\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankReconciliation extends Model
{
    use BelongsToCompany;
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_COMPLETED = 'completed';

    protected $table = 'accounting_bank_reconciliations';

    protected $fillable = [
        'company_id',
        'bank_account_id',
        'period_start',
        'period_end',
        'statement_ending_balance',
        'book_ending_balance',
        'cleared_balance',
        'status',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'statement_ending_balance' => 'decimal:2',
            'book_ending_balance' => 'decimal:2',
            'cleared_balance' => 'decimal:2',
            'completed_at' => 'datetime',
        ];
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function lines()
    {
        return $this->hasMany(BankReconciliationLine::class, 'bank_reconciliation_id')->orderBy('transaction_date')->orderBy('id');
    }
}