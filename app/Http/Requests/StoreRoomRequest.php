<?php

namespace App\Http\Requests;

use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = app(CurrentCompanyContext::class)->id();

        return [
            'property_id' => [
                'required',
                'integer',
                Rule::exists('hotel_properties', 'id')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'room_type_id' => [
                'required',
                'integer',
                Rule::exists('hotel_room_types', 'id')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'floor_label' => ['nullable', 'string', 'max:40'],
            'room_number' => ['required', 'string', 'max:40'],
            'status' => ['nullable', 'string', 'max:30'],
            'cleaning_status' => ['nullable', 'string', 'max:30'],
            'is_smoking_allowed' => ['nullable', 'boolean'],
        ];
    }
}