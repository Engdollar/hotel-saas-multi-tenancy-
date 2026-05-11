<?php

namespace App\Http\Requests;

use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePosKitchenStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = app(CurrentCompanyContext::class)->id();

        return [
            'line_ids' => ['nullable', 'array'],
            'line_ids.*' => ['integer', Rule::exists('hotel_pos_order_lines', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
        ];
    }
}