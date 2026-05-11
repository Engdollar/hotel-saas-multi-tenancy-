<?php

namespace App\Http\Requests;

use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $context = app(CurrentCompanyContext::class);
        $companyId = $context->bypassesTenancy() ? null : $context->id();

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('permissions', 'name')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
        ];
    }
}