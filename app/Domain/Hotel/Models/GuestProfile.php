<?php

namespace App\Domain\Hotel\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuestProfile extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $table = 'hotel_guest_profiles';

    protected $fillable = [
        'company_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'gender',
        'nationality',
        'address_line1',
        'address_line2',
        'city',
        'state_region',
        'postal_code',
        'country_code',
        'tax_identifier',
        'visa_number',
        'visa_expiry_date',
        'gdpr_consent_at',
        'marketing_consent_at',
        'passport_number',
        'passport_expiry_date',
        'loyalty_number',
        'is_vip',
        'is_blacklisted',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'passport_expiry_date' => 'date',
            'visa_expiry_date' => 'date',
            'gdpr_consent_at' => 'datetime',
            'marketing_consent_at' => 'datetime',
            'is_vip' => 'boolean',
            'is_blacklisted' => 'boolean',
        ];
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'guest_profile_id');
    }

    public function identityDocuments()
    {
        return $this->hasMany(GuestIdentityDocument::class)->latest();
    }
}