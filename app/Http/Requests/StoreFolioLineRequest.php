<?php

namespace App\Http\Requests;

use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFolioLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = app(CurrentCompanyContext::class)->id();

        return [
            'ledger_account_id' => ['nullable', 'integer', Rule::exists('accounting_ledger_accounts', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'line_type' => ['nullable', 'string', 'max:40'],
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['nullable', 'numeric', 'min:0.01'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
            'service_date' => ['nullable', 'date'],
        ];
    }
}