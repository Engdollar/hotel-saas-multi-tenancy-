<?php

namespace App\Http\Requests;

use App\Models\SupportTicket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'priority' => ['required', Rule::in(SupportTicket::priorities())],
            'description' => ['required', 'string', 'min:10', 'max:15000'],
        ];
    }
}
