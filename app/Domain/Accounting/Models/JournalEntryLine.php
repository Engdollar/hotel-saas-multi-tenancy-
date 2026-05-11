<?php

namespace App\Domain\Accounting\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalEntryLine extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $table = 'accounting_journal_entry_lines';

    protected $fillable = [
        'company_id',
        'journal_entry_id',
        'ledger_account_id',
        'description',
        'debit_amount',
        'credit_amount',
    ];

    protected function casts(): array
    {
        return [
            'debit_amount' => 'decimal:2',
            'credit_amount' => 'decimal:2',
        ];
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function ledgerAccount()
    {
        return $this->belongsTo(LedgerAccount::class, 'ledger_account_id');
    }
}