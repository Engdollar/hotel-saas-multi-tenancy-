<?php

namespace App\Domain\Hotel\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuestDocumentExtractionRequest extends Model
{
    use BelongsToCompany;
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $table = 'hotel_guest_document_extraction_requests';

    protected $fillable = [
        'company_id',
        'guest_identity_document_id',
        'reservation_id',
        'provider',
        'status',
        'extracted_payload',
        'failure_message',
        'requested_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'extracted_payload' => 'array',
            'requested_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function identityDocument()
    {
        return $this->belongsTo(GuestIdentityDocument::class, 'guest_identity_document_id');
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }
}