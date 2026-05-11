<?php

namespace App\Http\Requests;

use App\Domain\Accounting\Models\Payment;
use App\Domain\Accounting\Models\SupplierPayment;
use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBankReconciliationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = app(CurrentCompanyContext::class)->id();

        return [
            'bank_account_id' => ['required', 'integer', Rule::exists('accounting_bank_accounts', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'statement_ending_balance' => ['required', 'numeric'],
            'book_ending_balance' => ['nullable', 'numeric'],
            'status' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],
            'lines' => ['nullable', 'array'],
            'lines.*.entry_type' => ['nullable', 'string', 'max:40'],
            'lines.*.reference_type' => ['nullable', Rule::in([Payment::class, SupplierPayment::class])],
            'lines.*.reference_id' => ['nullable', 'integer'],
            'lines.*.description' => ['required_without:lines.*.reference_type', 'nullable', 'string', 'max:255'],
            'lines.*.transaction_date' => ['required_without:lines.*.reference_type', 'nullable', 'date'],
            'lines.*.amount' => ['required_without:lines.*.reference_type', 'nullable', 'numeric'],
            'lines.*.is_cleared' => ['nullable', 'boolean'],
        ];
    }
}