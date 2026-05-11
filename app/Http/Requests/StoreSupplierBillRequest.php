<?php

namespace App\Http\Requests;

use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierBillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = app(CurrentCompanyContext::class)->id();

        return [
            'supplier_id' => ['required', 'integer', Rule::exists('accounting_suppliers', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'purchase_order_id' => ['nullable', 'integer', Rule::exists('procurement_purchase_orders', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'status' => ['nullable', 'string', 'max:30'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'bill_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.purchase_order_line_id' => ['nullable', 'integer', Rule::exists('procurement_purchase_order_lines', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'lines.*.inventory_item_id' => ['nullable', 'integer', Rule::exists('inventory_items', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'lines.*.ledger_account_id' => ['nullable', 'integer', Rule::exists('accounting_ledger_accounts', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.total_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}