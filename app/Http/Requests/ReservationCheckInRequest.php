<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReservationCheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'actual_check_in_at' => ['nullable', 'date'],
            'signature_name' => ['nullable', 'string', 'max:255'],
            'signature_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'identity_documents' => ['nullable', 'array'],
            'identity_documents.*.document_type' => ['required', 'string', 'max:40'],
            'identity_documents.*.document_number' => ['nullable', 'string', 'max:120'],
            'identity_documents.*.issuing_country' => ['nullable', 'string', 'max:100'],
            'identity_documents.*.issued_at' => ['nullable', 'date'],
            'identity_documents.*.expires_at' => ['nullable', 'date'],
            'identity_documents.*.file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
            'identity_documents.*.is_primary' => ['nullable', 'boolean'],
            'identity_documents.*.notes' => ['nullable', 'string'],
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