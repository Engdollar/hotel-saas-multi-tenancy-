<?php

namespace App\Domain\Hotel\Models;

use App\Domain\Accounting\Models\LedgerAccount;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FolioLine extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $table = 'hotel_folio_lines';

    protected $fillable = [
        'company_id',
        'folio_id',
        'ledger_account_id',
        'line_type',
        'description',
        'quantity',
        'unit_price',
        'tax_amount',
        'total_amount',
        'service_date',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'service_date' => 'date',
        ];
    }

    public function folio()
    {
        return $this->belongsTo(Folio::class);
    }

    public function ledgerAccount()
    {
        return $this->belongsTo(LedgerAccount::class);
    }
}