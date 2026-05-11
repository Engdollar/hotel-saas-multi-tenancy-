<?php

namespace App\Http\Requests;

use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBankAccountRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_number_last4' => ['nullable', 'string', 'max:10'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'current_balance' => ['nullable', 'numeric'],
            'is_active' => ['nullable', 'boolean'],
            'opened_at' => ['nullable', 'date'],
        ];
    }
}