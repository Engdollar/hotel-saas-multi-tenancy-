<?php

namespace App\Http\Requests;

use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = app(CurrentCompanyContext::class)->id();

        return [
            'payment_id' => ['nullable', 'integer', Rule::exists('accounting_payments', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'refunded_at' => ['nullable', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string'],
        ];
    }
}