<?php

namespace App\Domain\Accounting\Models;

use App\Domain\Hotel\Models\Folio;
use App\Domain\Hotel\Models\GuestProfile;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use BelongsToCompany;
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ISSUED = 'issued';
    public const STATUS_PAID = 'paid';
    public const STATUS_PARTIALLY_PAID = 'partially_paid';
    public const STATUS_VOID = 'void';
    public const STATUS_REFUNDED = 'refunded';

    protected $table = 'accounting_invoices';

    protected $fillable = [
        'company_id',
        'guest_profile_id',
        'folio_id',
        'invoice_number',
        'status',
        'currency_code',
        'issue_date',
        'due_date',
        'subtotal_amount',
        'tax_amount',
        'total_amount',
        'balance_amount',
        'source_type',
        'source_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'balance_amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $invoice): void {
            if (! $invoice->invoice_number) {
                $invoice->invoice_number = 'INV-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
            }

            $invoice->issue_date ??= now()->toDateString();
        });
    }

    public function guestProfile()
    {
        return $this->belongsTo(GuestProfile::class, 'guest_profile_id');
    }

    public function folio()
    {
        return $this->belongsTo(Folio::class);
    }

    public function lines()
    {
        return $this->hasMany(InvoiceLine::class)->orderBy('id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class)->orderByDesc('paid_at');
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class)->orderByDesc('refunded_at');
    }
}