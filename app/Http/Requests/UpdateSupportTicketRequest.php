<?php

namespace App\Http\Requests;

use App\Models\SupportTicket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(SupportTicket::statuses())],
            'priority' => ['required', Rule::in(SupportTicket::priorities())],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
