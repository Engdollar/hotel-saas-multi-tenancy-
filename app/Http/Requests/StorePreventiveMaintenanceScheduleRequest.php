<?php

namespace App\Http\Requests;

use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePreventiveMaintenanceScheduleRequest extends FormRequest
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
            'room_id' => ['nullable', 'integer', Rule::exists('hotel_rooms', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'assigned_to_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'maintenance_category' => ['nullable', 'string', 'max:40'],
            'priority' => ['nullable', 'string', 'max:20'],
            'frequency_days' => ['required', 'integer', 'min:1', 'max:365'],
            'next_due_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}