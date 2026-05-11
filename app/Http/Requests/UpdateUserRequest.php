<?php

namespace App\Http\Requests;

use App\Support\Tenancy\CurrentCompanyContext;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class)->ignore($this->route('user'))],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'profile_image' => ['nullable', 'image', 'max:2048'],
            'roles' => ['nullable', 'array'],
            'roles.*' => [
                'string',
                Rule::exists('roles', 'name')->where(fn ($query) => $query
                    ->where('company_id', $companyId)
                    ->where('is_locked', false)),
            ],
        ];
    }
}