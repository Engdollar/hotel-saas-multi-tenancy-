<?php

namespace App\Http\Requests;

use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $context = app(CurrentCompanyContext::class);
        $companyId = $context->id();

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')
                    ->where(fn ($query) => $query->where('company_id', $companyId))
                    ->ignore($this->route('role')),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [
                'string',
                Rule::exists('permissions', 'name')->where(function ($query) use ($companyId) {
                    $query->where('company_id', $companyId)->orWhereNull('company_id');
                }),
            ],
        ];
    }
}