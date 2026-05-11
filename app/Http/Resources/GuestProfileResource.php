<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuestProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'date_of_birth' => $this->date_of_birth,
            'gender' => $this->gender,
            'nationality' => $this->nationality,
            'address_line1' => $this->address_line1,
            'address_line2' => $this->address_line2,
            'city' => $this->city,
            'state_region' => $this->state_region,
            'postal_code' => $this->postal_code,
            'country_code' => $this->country_code,
            'tax_identifier' => $this->tax_identifier,
            'visa_number' => $this->visa_number,
            'visa_expiry_date' => $this->visa_expiry_date,
            'gdpr_consent_at' => $this->gdpr_consent_at,
            'marketing_consent_at' => $this->marketing_consent_at,
            'passport_number' => $this->passport_number,
            'passport_expiry_date' => $this->passport_expiry_date,
            'loyalty_number' => $this->loyalty_number,
            'is_vip' => $this->is_vip,
            'is_blacklisted' => $this->is_blacklisted,
            'notes' => $this->notes,
            'identity_documents' => GuestIdentityDocumentResource::collection($this->whenLoaded('identityDocuments')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}