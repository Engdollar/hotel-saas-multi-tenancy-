<?php

namespace App\Http\Requests;

use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReceivePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = app(CurrentCompanyContext::class)->id();

        return [
            'received_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.purchase_order_line_id' => ['required', 'integer', Rule::exists('procurement_purchase_order_lines', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'lines.*.received_quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}