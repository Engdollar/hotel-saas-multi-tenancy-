<?php

namespace App\Domain\Accounting\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class JournalEntry extends Model
{
    use BelongsToCompany;
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_POSTED = 'posted';
    public const STATUS_VOID = 'void';

    protected $table = 'accounting_journal_entries';

    protected $fillable = [
        'company_id',
        'entry_number',
        'entry_date',
        'currency_code',
        'source_type',
        'source_id',
        'description',
        'status',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'posted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $entry): void {
            if (! $entry->entry_number) {
                $entry->entry_number = 'JRN-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
            }
        });
    }

    public function lines()
    {
        return $this->hasMany(JournalEntryLine::class);
    }
}