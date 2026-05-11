<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGeneratedThemePresetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'generated_presets' => ['required', 'string'],
            'selected_presets' => ['required', 'array', 'min:1'],
            'selected_presets.*' => ['required', 'string'],
            'replace_existing' => ['nullable', 'boolean'],
        ];
    }
}