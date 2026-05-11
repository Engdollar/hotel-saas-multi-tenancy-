<?php

namespace App\Http\Requests;

use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceRequest extends FormRequest
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
            'reported_by_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'assigned_to_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'maintenance_category' => ['nullable', 'string', 'max:40'],
            'priority' => ['nullable', 'string', 'max:20'],
            'is_preventive' => ['nullable', 'boolean'],
            'preventive_maintenance_schedule_id' => ['nullable', 'integer', Rule::exists('hotel_preventive_maintenance_schedules', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'status' => ['nullable', 'string', 'max:30'],
            'reported_at' => ['nullable', 'date'],
            'scheduled_for' => ['nullable', 'date'],
            'technician_notes' => ['nullable', 'string'],
        ];
    }
}