<?php

namespace App\Http\Requests;

use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryItemRequest extends FormRequest
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
            'preferred_supplier_id' => ['nullable', 'integer', Rule::exists('accounting_suppliers', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'sku' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:80'],
            'unit_of_measure' => ['nullable', 'string', 'max:30'],
            'current_quantity' => ['nullable', 'numeric'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'par_level' => ['nullable', 'numeric', 'min:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}