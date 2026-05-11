<?php

namespace Tests\Feature;

use App\Domain\Hotel\Models\GuestDocumentExtractionRequest;
use App\Domain\Hotel\Models\Reservation;
use App\Domain\Hotel\Models\RoomType;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HotelPreArrivalRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pre_arrival_registration_captures_compliance_documents_and_ocr_hooks(): void
    {
        Storage::fake('public');

        $company = Company::query()->create(['name' => 'North Star Hotels', 'status' => Company::STATUS_ACTIVE]);
        $user = $this->createTenantApiUser($company, [
            'create-property', 'read-property', 'show-property',
            'create-room', 'read-room', 'show-room',
            'create-guest', 'read-guest', 'show-guest', 'update-guest',
            'create-reservation', 'read-reservation', 'show-reservation', 'update-reservation',
        ]);

        $propertyId = $this->actingAs($user)->postJson(route('api.v1.properties.store'), [
            'branch_code' => 'NST-HQ',
            'name' => 'North Star Downtown',
            'property_type' => 'hotel',
            'timezone' => 'UTC',
            'currency_code' => 'USD',
            'status' => 'active',
        ])->json('data.id');

        app(CurrentCompanyContext::class)->set($company->id);

        $roomType = RoomType::query()->create([
            'property_id' => $propertyId,
            'name' => 'Premier King',
            'code' => 'PK',
            'base_rate' => 220,
            'capacity_adults' => 2,
            'capacity_children' => 1,
            'status' => 'active',
        ]);

        $guestId = $this->actingAs($user)->postJson(route('api.v1.guests.store'), [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
        ])->json('data.id');

        $roomId = $this->actingAs($user)->postJson(route('api.v1.rooms.store'), [
            'property_id' => $propertyId,
            'room_type_id' => $roomType->id,
            'room_number' => '1201',
            'status' => 'available',
            'cleaning_status' => 'clean',
        ])->json('data.id');

        $reservationId = $this->actingAs($user)->postJson(route('api.v1.reservations.store'), [
            'property_id' => $propertyId,
            'room_id' => $roomId,
            'guest_profile_id' => $guestId,
            'booking_source' => 'direct',
            'currency_code' => 'USD',
            'status' => Reservation::STATUS_CONFIRMED,
            'check_in_date' => '2026-08-10',
            'check_out_date' => '2026-08-12',
            'adult_count' => 1,
            'rate_amount' => 440,
            'tax_amount' => 44,
            'total_amount' => 484,
        ])->json('data.id');

        $response = $this->actingAs($user)->post(route('api.v1.reservations.pre-arrival-registration', $reservationId), [
            'expected_arrival_time' => '18:30',
            'registration_channel' => 'self_service',
            'emergency_contact_name' => 'Lord Byron',
            'emergency_contact_phone' => '+44123456789',
            'compliance_notes' => 'Guest requested manual visa verification on arrival.',
            'special_requests' => 'Feather-free pillows.',
            'signature_name' => 'Ada Lovelace',
            'signature_file' => UploadedFile::fake()->image('prearrival-signature.png'),
            'guest' => [
                'date_of_birth' => '1815-12-10',
                'gender' => 'female',
                'nationality' => 'GB',
                'address_line1' => '12 Analytical Engine Row',
                'city' => 'London',
                'state_region' => 'Greater London',
                'postal_code' => 'SW1A 1AA',
                'country_code' => 'GB',
                'tax_identifier' => 'GB-ADA-001',
                'visa_number' => 'VISA-1815',
                'visa_expiry_date' => '2027-08-12',
                'gdpr_consent' => true,
                'marketing_consent' => true,
            ],
            'identity_documents' => [
                [
                    'document_type' => 'passport',
                    'document_number' => 'GB1234567',
                    'issuing_country' => 'GB',
                    'expires_at' => '2031-01-01',
                    'file' => UploadedFile::fake()->image('passport.png'),
                    'is_primary' => true,
                    'request_ocr' => true,
                    'ocr_provider' => 'mock-ocr',
                ],
            ],
            'visitors' => [
                [
                    'full_name' => 'Charles Babbage',
                    'relationship_to_guest' => 'business associate',
                    'identification_number' => 'VIS-2026-1',
                    'notes' => 'Expected for lobby meeting.',
                ],
            ],
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.pre_arrival_status', 'completed');
        $response->assertJsonPath('data.registration_channel', 'self_service');
        $response->assertJsonPath('data.guest.country_code', 'GB');
        $response->assertJsonPath('data.identity_documents.0.extraction_requests.0.status', GuestDocumentExtractionRequest::STATUS_PENDING);
        $response->assertJsonPath('data.visitors.0.full_name', 'Charles Babbage');

        $this->assertDatabaseHas('hotel_guest_profiles', [
            'id' => $guestId,
            'country_code' => 'GB',
            'tax_identifier' => 'GB-ADA-001',
            'visa_number' => 'VISA-1815',
        ]);

        $this->assertDatabaseHas('hotel_reservations', [
            'id' => $reservationId,
            'pre_arrival_status' => 'completed',
            'registration_channel' => 'self_service',
            'emergency_contact_name' => 'Lord Byron',
        ]);

        $this->assertDatabaseHas('hotel_guest_document_extraction_requests', [
            'reservation_id' => $reservationId,
            'provider' => 'mock-ocr',
            'status' => GuestDocumentExtractionRequest::STATUS_PENDING,
        ]);

        $reservation = Reservation::query()->findOrFail($reservationId);
        Storage::disk('public')->assertExists($reservation->check_in_signature_path);
    }

    protected function createTenantApiUser(Company $company, array $permissionNames): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);

        $role = Role::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Pre Arrival API Operator '.$company->id.'-'.str()->random(5),
            'guard_name' => 'web',
        ]);

        $permissions = collect($permissionNames)->map(fn (string $permissionName) => Permission::withoutGlobalScopes()->firstOrCreate([
            'company_id' => null,
            'name' => $permissionName,
            'guard_name' => 'web',
        ]));

        $role->syncPermissions($permissions);
        $user->assignRole($role);

        return $user;
    }
}