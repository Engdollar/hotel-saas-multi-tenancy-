<?php

namespace App\Http\Requests;

use App\Services\SettingsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_title' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'favicon' => ['nullable', 'image', 'max:1024'],
            'tenancy_base_domain' => ['nullable', 'string', 'max:255'],
            'theme_preset' => ['required', Rule::in(app(SettingsService::class)->presetKeys())],
            'theme_mode' => ['required', 'in:light,dark,system'],
        ];
    }
}