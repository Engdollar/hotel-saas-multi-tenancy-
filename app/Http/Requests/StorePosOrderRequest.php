<?php

namespace App\Http\Requests;

use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePosOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = app(CurrentCompanyContext::class)->id();

        return [
            'property_id' => ['required', 'integer', Rule::exists('hotel_properties', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'cashier_shift_id' => ['nullable', 'integer', Rule::exists('hotel_pos_cashier_shifts', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'reservation_id' => ['nullable', 'integer', Rule::exists('hotel_reservations', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'folio_id' => ['nullable', 'integer', Rule::exists('hotel_folios', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'payment_method' => ['nullable', 'string', 'max:30'],
            'service_location' => ['nullable', 'string', 'max:40'],
            'charge_to_room' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.ledger_account_id' => ['nullable', 'integer', Rule::exists('accounting_ledger_accounts', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'lines.*.inventory_item_id' => ['nullable', 'integer', Rule::exists('inventory_items', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'lines.*.item_name' => ['nullable', 'string', 'max:255', Rule::requiredIf(fn () => ! $this->input('lines.*.inventory_item_id'))],
            'lines.*.category' => ['nullable', 'string', 'max:60'],
            'lines.*.modifiers' => ['nullable', 'array'],
            'lines.*.modifiers.*.name' => ['required_with:lines.*.modifiers', 'string', 'max:120'],
            'lines.*.modifiers.*.quantity' => ['nullable', 'numeric', 'min:0.01'],
            'lines.*.modifiers.*.price' => ['nullable', 'numeric', 'min:0'],
            'lines.*.kitchen_station' => ['nullable', 'string', 'max:60'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.total_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}