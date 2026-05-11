<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClosePosCashierShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'closing_cash_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}