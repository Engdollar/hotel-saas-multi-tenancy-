<?php

namespace App\Domain\Accounting\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $table = 'accounting_bank_accounts';

    protected $fillable = [
        'company_id',
        'ledger_account_id',
        'name',
        'bank_name',
        'account_number_last4',
        'currency_code',
        'current_balance',
        'is_active',
        'opened_at',
    ];

    protected function casts(): array
    {
        return [
            'current_balance' => 'decimal:2',
            'is_active' => 'boolean',
            'opened_at' => 'datetime',
        ];
    }

    public function ledgerAccount()
    {
        return $this->belongsTo(LedgerAccount::class, 'ledger_account_id');
    }

    public function reconciliations()
    {
        return $this->hasMany(BankReconciliation::class)->latest('period_end');
    }
}