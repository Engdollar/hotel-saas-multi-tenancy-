<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:60'],
            'tax_identifier' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],
        ];
    }
}