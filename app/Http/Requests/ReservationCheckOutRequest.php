<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReservationCheckOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'actual_check_out_at' => ['nullable', 'date'],
            'housekeeping_notes' => ['nullable', 'string'],
        ];
    }
}