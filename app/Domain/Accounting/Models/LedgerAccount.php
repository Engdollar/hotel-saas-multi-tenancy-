<?php

namespace App\Domain\Accounting\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LedgerAccount extends Model
{
    use BelongsToCompany;
    use HasFactory;

    public const TYPE_ASSET = 'asset';
    public const TYPE_LIABILITY = 'liability';
    public const TYPE_EQUITY = 'equity';
    public const TYPE_INCOME = 'income';
    public const TYPE_EXPENSE = 'expense';

    protected $table = 'accounting_ledger_accounts';

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'type',
        'subtype',
        'currency_code',
        'is_system',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function journalLines()
    {
        return $this->hasMany(JournalEntryLine::class, 'ledger_account_id');
    }
}