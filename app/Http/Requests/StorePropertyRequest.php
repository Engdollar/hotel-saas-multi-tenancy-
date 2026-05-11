<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_code' => ['required', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:255'],
            'property_type' => ['required', 'string', 'max:50'],
            'timezone' => ['required', 'string', 'max:100'],
            'currency_code' => ['required', 'string', 'size:3'],
            'check_in_time' => ['nullable', 'date_format:H:i'],
            'check_out_time' => ['nullable', 'date_format:H:i'],
            'status' => ['nullable', 'string', 'max:30'],
        ];
    }
}