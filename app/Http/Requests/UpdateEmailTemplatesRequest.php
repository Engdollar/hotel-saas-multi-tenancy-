<?php

namespace App\Http\Requests;

use App\Services\SettingsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmailTemplatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $settings = app(SettingsService::class);

        return [
            'email_template' => ['required', Rule::in($settings->templateKeys(SettingsService::EMAIL_TEMPLATE_TYPE))],
            'email_subject' => ['required', 'string', 'max:255'],
            'email_headline' => ['required', 'string', 'max:255'],
            'email_greeting' => ['required', 'string', 'max:255'],
            'email_body_html' => ['required', 'string'],
            'email_button_label' => ['required', 'string', 'max:255'],
            'email_signature' => ['required', 'string'],
            'email_accent' => ['required', 'string', 'max:20'],
            'email_surface' => ['required', 'string', 'max:20'],
        ];
    }
}
