<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReservationPreArrivalRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expected_arrival_time' => ['nullable', 'date_format:H:i'],
            'registration_channel' => ['nullable', 'string', 'max:40'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
            'compliance_notes' => ['nullable', 'string'],
            'special_requests' => ['nullable', 'string'],
            'signature_name' => ['nullable', 'string', 'max:255'],
            'signature_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'guest' => ['nullable', 'array'],
            'guest.first_name' => ['sometimes', 'string', 'max:255'],
            'guest.last_name' => ['sometimes', 'string', 'max:255'],
            'guest.email' => ['nullable', 'email', 'max:255'],
            'guest.phone' => ['nullable', 'string', 'max:50'],
            'guest.date_of_birth' => ['nullable', 'date'],
            'guest.gender' => ['nullable', 'string', 'max:30'],
            'guest.nationality' => ['nullable', 'string', 'max:100'],
            'guest.address_line1' => ['nullable', 'string', 'max:255'],
            'guest.address_line2' => ['nullable', 'string', 'max:255'],
            'guest.city' => ['nullable', 'string', 'max:120'],
            'guest.state_region' => ['nullable', 'string', 'max:120'],
            'guest.postal_code' => ['nullable', 'string', 'max:40'],
            'guest.country_code' => ['nullable', 'string', 'size:2'],
            'guest.tax_identifier' => ['nullable', 'string', 'max:100'],
            'guest.visa_number' => ['nullable', 'string', 'max:120'],
            'guest.visa_expiry_date' => ['nullable', 'date'],
            'guest.gdpr_consent' => ['nullable', 'boolean'],
            'guest.marketing_consent' => ['nullable', 'boolean'],
            'identity_documents' => ['nullable', 'array'],
            'identity_documents.*.document_type' => ['required', 'string', 'max:40'],
            'identity_documents.*.document_number' => ['nullable', 'string', 'max:120'],
            'identity_documents.*.issuing_country' => ['nullable', 'string', 'max:100'],
            'identity_documents.*.issued_at' => ['nullable', 'date'],
            'identity_documents.*.expires_at' => ['nullable', 'date'],
            'identity_documents.*.file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
            'identity_documents.*.is_primary' => ['nullable', 'boolean'],
            'identity_documents.*.notes' => ['nullable', 'string'],
            'identity_documents.*.request_ocr' => ['nullable', 'boolean'],
            'identity_documents.*.ocr_provider' => ['nullable', 'string', 'max:60'],
            'visitors' => ['nullable', 'array'],
            'visitors.*.full_name' => ['required', 'string', 'max:255'],
            'visitors.*.relationship_to_guest' => ['nullable', 'string', 'max:80'],
            'visitors.*.identification_number' => ['nullable', 'string', 'max:120'],
            'visitors.*.phone' => ['nullable', 'string', 'max:50'],
            'visitors.*.checked_in_at' => ['nullable', 'date'],
            'visitors.*.checked_out_at' => ['nullable', 'date'],
            'visitors.*.notes' => ['nullable', 'string'],
        ];
    }
}