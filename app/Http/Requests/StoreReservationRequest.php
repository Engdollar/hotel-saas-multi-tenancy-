<?php

namespace App\Http\Requests;

use App\Domain\Hotel\Models\Reservation;
use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReservationRequest extends FormRequest
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
            'guest_profile_id' => ['required', 'integer', Rule::exists('hotel_guest_profiles', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'booking_source' => ['nullable', 'string', 'max:50'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'status' => ['nullable', Rule::in([
                Reservation::STATUS_PENDING,
                Reservation::STATUS_CONFIRMED,
                Reservation::STATUS_CHECKED_IN,
                Reservation::STATUS_CHECKED_OUT,
                Reservation::STATUS_CANCELLED,
                Reservation::STATUS_NO_SHOW,
            ])],
            'check_in_date' => ['required', 'date'],
            'check_out_date' => ['required', 'date', 'after:check_in_date'],
            'adult_count' => ['required', 'integer', 'min:1'],
            'child_count' => ['nullable', 'integer', 'min:0'],
            'rate_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
            'special_requests' => ['nullable', 'string'],
        ];
    }
}