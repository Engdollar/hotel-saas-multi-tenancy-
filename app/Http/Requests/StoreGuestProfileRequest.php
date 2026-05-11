<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGuestProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:30'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state_region' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:40'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'tax_identifier' => ['nullable', 'string', 'max:100'],
            'visa_number' => ['nullable', 'string', 'max:120'],
            'visa_expiry_date' => ['nullable', 'date'],
            'gdpr_consent_at' => ['nullable', 'date'],
            'marketing_consent_at' => ['nullable', 'date'],
            'passport_number' => ['nullable', 'string', 'max:100'],
            'passport_expiry_date' => ['nullable', 'date'],
            'loyalty_number' => ['nullable', 'string', 'max:100'],
            'is_vip' => ['nullable', 'boolean'],
            'is_blacklisted' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}