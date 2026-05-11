<?php

namespace App\Http\Requests;

use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = app(CurrentCompanyContext::class)->id();

        return [
            'property_id' => ['nullable', 'integer', Rule::exists('hotel_properties', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'supplier_id' => ['required', 'integer', Rule::exists('accounting_suppliers', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'status' => ['nullable', 'string', 'max:30'],
            'quantity_tolerance_percent' => ['nullable', 'numeric', 'min:0'],
            'amount_tolerance_percent' => ['nullable', 'numeric', 'min:0'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'order_date' => ['nullable', 'date'],
            'expected_delivery_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'approval_steps' => ['nullable', 'array'],
            'approval_steps.*.approver_user_id' => ['required', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'approval_steps.*.notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.inventory_item_id' => ['required', 'integer', Rule::exists('inventory_items', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.ordered_quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.total_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}