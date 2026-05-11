<?php

namespace Tests\Feature;

use App\Domain\Hotel\Models\GuestProfile;
use App\Domain\Hotel\Models\Property;
use App\Domain\Hotel\Models\Reservation;
use App\Domain\Hotel\Models\Room;
use App\Domain\Hotel\Models\RoomType;
use App\Domain\Hotel\Services\ReservationConflictService;
use App\Models\Company;
use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HotelReservationFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_scope_is_applied_to_hotel_models(): void
    {
        $companyA = Company::query()->create(['name' => 'Alpha Hotel Group', 'status' => Company::STATUS_ACTIVE]);
        $companyB = Company::query()->create(['name' => 'Beta Hotel Group', 'status' => Company::STATUS_ACTIVE]);

        app(CurrentCompanyContext::class)->set($companyA->id);
        $alphaProperty = Property::query()->create([
            'branch_code' => 'ALPHA-HQ',
            'name' => 'Alpha Downtown Hotel',
            'property_type' => 'hotel',
            'timezone' => 'UTC',
            'currency_code' => 'USD',
        ]);

        app(CurrentCompanyContext::class)->set($companyB->id);
        Property::query()->create([
            'branch_code' => 'BETA-HQ',
            'name' => 'Beta Beach Resort',
            'property_type' => 'resort',
            'timezone' => 'UTC',
            'currency_code' => 'USD',
        ]);

        app(CurrentCompanyContext::class)->set($companyA->id);

        $this->assertCount(1, Property::query()->get());
        $this->assertSame($alphaProperty->id, Property::query()->firstOrFail()->id);
    }

    public function test_reservation_conflict_service_detects_overlap_within_same_company_scope(): void
    {
        $company = Company::query()->create(['name' => 'Alpha Hotel Group', 'status' => Company::STATUS_ACTIVE]);
        app(CurrentCompanyContext::class)->set($company->id);

        $property = Property::query()->create([
            'branch_code' => 'ALPHA-HQ',
            'name' => 'Alpha Downtown Hotel',
            'property_type' => 'hotel',
            'timezone' => 'UTC',
            'currency_code' => 'USD',
        ]);

        $roomType = RoomType::query()->create([
            'property_id' => $property->id,
            'name' => 'Deluxe King',
            'code' => 'DLX-K',
            'base_rate' => 150,
            'capacity_adults' => 2,
            'capacity_children' => 1,
        ]);

        $room = Room::query()->create([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'room_number' => '101',
        ]);

        $guest = GuestProfile::query()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ]);

        Reservation::query()->create([
            'property_id' => $property->id,
            'room_id' => $room->id,
            'guest_profile_id' => $guest->id,
            'status' => Reservation::STATUS_CONFIRMED,
            'check_in_date' => '2026-05-10',
            'check_out_date' => '2026-05-13',
            'adult_count' => 2,
            'total_amount' => 450,
        ]);

        $service = app(ReservationConflictService::class);

        $this->assertTrue($service->hasConflict($room->id, now()->parse('2026-05-11'), now()->parse('2026-05-12')));
        $this->assertFalse($service->hasConflict($room->id, now()->parse('2026-05-13'), now()->parse('2026-05-15')));
    }
}