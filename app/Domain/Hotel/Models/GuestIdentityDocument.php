<?php

namespace App\Domain\Hotel\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuestIdentityDocument extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $table = 'hotel_guest_identity_documents';

    protected $fillable = [
        'company_id',
        'guest_profile_id',
        'reservation_id',
        'verified_by_user_id',
        'document_type',
        'document_number',
        'issuing_country',
        'issued_at',
        'expires_at',
        'file_path',
        'is_primary',
        'verified_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'expires_at' => 'date',
            'is_primary' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function guestProfile()
    {
        return $this->belongsTo(GuestProfile::class);
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    public function extractionRequests()
    {
        return $this->hasMany(GuestDocumentExtractionRequest::class, 'guest_identity_document_id')->latest();
    }
}