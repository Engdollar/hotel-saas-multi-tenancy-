<?php

namespace App\Http\Requests;

use App\Services\ThemePresetGeneratorService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateThemePresetPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keywords' => ['nullable', 'string', 'max:500'],
            'packs' => ['nullable', 'array'],
            'packs.*' => ['string', Rule::in(array_keys(app(ThemePresetGeneratorService::class)->keywordPacks()))],
            'count' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }
}