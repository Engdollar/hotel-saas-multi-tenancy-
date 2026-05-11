<?php

namespace App\Http\Requests;

use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHousekeepingTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = app(CurrentCompanyContext::class)->id();

        return [
            'property_id' => ['required', 'integer', Rule::exists('hotel_properties', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'room_id' => ['required', 'integer', Rule::exists('hotel_rooms', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'reservation_id' => ['nullable', 'integer', Rule::exists('hotel_reservations', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'assigned_to_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'task_type' => ['required', 'string', 'max:40'],
            'status' => ['nullable', 'string', 'max:30'],
            'priority' => ['nullable', 'string', 'max:20'],
            'linen_status' => ['nullable', 'string', 'max:30'],
            'linen_items_collected' => ['nullable', 'integer', 'min:0'],
            'linen_items_delivered' => ['nullable', 'integer', 'min:0'],
            'minibar_status' => ['nullable', 'string', 'max:30'],
            'minibar_charge_amount' => ['nullable', 'numeric', 'min:0'],
            'inspection_status' => ['nullable', 'string', 'max:30'],
            'inspected_by_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'inspection_notes' => ['nullable', 'string'],
            'scheduled_for' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}