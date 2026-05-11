<?php

namespace App\Http\Requests;

use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReservationRoomMoveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = app(CurrentCompanyContext::class)->id();

        return [
            'to_room_id' => ['required', 'integer', Rule::exists('hotel_rooms', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'reason' => ['nullable', 'string'],
        ];
    }
}