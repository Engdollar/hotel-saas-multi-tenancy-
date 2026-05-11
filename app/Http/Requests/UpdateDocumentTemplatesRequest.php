<?php

namespace App\Http\Requests;

use App\Services\SettingsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocumentTemplatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $settings = app(SettingsService::class);

        $pdfRules = [
            'pdf_header_template' => ['required', Rule::in($settings->templateKeys(SettingsService::PDF_HEADER_TEMPLATE_TYPE))],
            'pdf_header_kicker' => ['required', 'string', 'max:255'],
            'pdf_header_title' => ['required', 'string', 'max:255'],
            'pdf_header_subtitle' => ['nullable', 'string', 'max:255'],
            'pdf_header_accent_start' => ['required', 'string', 'max:20'],
            'pdf_header_accent_end' => ['required', 'string', 'max:20'],
            'pdf_header_surface' => ['required', 'string', 'max:20'],
            'pdf_header_border' => ['required', 'string', 'max:20'],
            'pdf_header_text_primary' => ['required', 'string', 'max:20'],
            'pdf_header_text_muted' => ['required', 'string', 'max:20'],
            'pdf_header_heading_background' => ['required', 'string', 'max:20'],
            'pdf_header_heading_text' => ['required', 'string', 'max:20'],
        ];

        $excelRules = [
            'excel_header_template' => ['required', Rule::in($settings->templateKeys(SettingsService::EXCEL_HEADER_TEMPLATE_TYPE))],
            'excel_header_title' => ['required', 'string', 'max:255'],
            'excel_header_subtitle' => ['nullable', 'string', 'max:255'],
            'excel_header_accent' => ['required', 'string', 'max:20'],
            'excel_header_title_text' => ['required', 'string', 'max:20'],
            'excel_header_meta_background' => ['required', 'string', 'max:20'],
            'excel_header_meta_text' => ['required', 'string', 'max:20'],
            'excel_header_heading_background' => ['required', 'string', 'max:20'],
            'excel_header_heading_text' => ['required', 'string', 'max:20'],
            'excel_header_body_border' => ['required', 'string', 'max:20'],
        ];

        return match ($this->input('template_scope')) {
            'pdf' => array_merge([
                'template_scope' => ['required', 'in:pdf,excel'],
            ], $pdfRules),
            'excel' => array_merge([
                'template_scope' => ['required', 'in:pdf,excel'],
            ], $excelRules),
            default => array_merge([
                'template_scope' => ['required', 'in:pdf,excel'],
            ], $pdfRules, $excelRules),
        };
    }
}
