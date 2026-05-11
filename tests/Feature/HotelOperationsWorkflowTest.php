<?php

namespace Tests\Feature;

use App\Domain\Hotel\Models\HousekeepingTask;
use App\Domain\Hotel\Models\MaintenanceRequest;
use App\Domain\Hotel\Models\PreventiveMaintenanceSchedule;
use App\Domain\Hotel\Models\Property;
use App\Domain\Hotel\Models\Reservation;
use App\Domain\Hotel\Models\Room;
use App\Domain\Hotel\Models\RoomMove;
use App\Domain\Hotel\Models\RoomType;
use App\Domain\Hotel\Models\RoomInspection;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HotelOperationsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_modules_execute_check_in_move_checkout_housekeeping_and_maintenance(): void
    {
        Storage::fake('public');

        $company = Company::query()->create(['name' => 'Vertex Hotel Group', 'status' => Company::STATUS_ACTIVE]);
        $technician = User::factory()->create(['company_id' => $company->id]);
        $user = $this->createTenantApiUser($company, [
            'create-property', 'read-property', 'show-property',
            'create-room', 'read-room', 'show-room', 'update-room',
            'create-guest', 'read-guest', 'show-guest',
            'create-reservation', 'read-reservation', 'show-reservation', 'update-reservation',
            'read-housekeeping-task', 'show-housekeeping-task', 'create-housekeeping-task', 'update-housekeeping-task',
            'read-maintenance-request', 'show-maintenance-request', 'create-maintenance-request', 'update-maintenance-request',
            'read-preventive-maintenance-schedule', 'show-preventive-maintenance-schedule', 'create-preventive-maintenance-schedule', 'update-preventive-maintenance-schedule',
        ]);

        $propertyResponse = $this->actingAs($user)->postJson(route('api.v1.properties.store'), [
            'branch_code' => 'VTX-HQ',
            'name' => 'Vertex Central',
            'property_type' => 'hotel',
            'timezone' => 'UTC',
            'currency_code' => 'USD',
            'status' => 'active',
        ]);

        $propertyResponse->assertCreated();
        $propertyId = $propertyResponse->json('data.id');

        app(CurrentCompanyContext::class)->set($company->id);

        $roomType = RoomType::query()->create([
            'property_id' => $propertyId,
            'name' => 'Executive',
            'code' => 'EXEC',
            'base_rate' => 300,
            'capacity_adults' => 2,
            'capacity_children' => 0,
            'status' => 'active',
        ]);

        $guestResponse = $this->actingAs($user)->postJson(route('api.v1.guests.store'), [
            'first_name' => 'Linus',
            'last_name' => 'Torvalds',
            'email' => 'linus@example.com',
        ]);

        $guestResponse->assertCreated();
        $guestId = $guestResponse->json('data.id');

        $roomOne = $this->actingAs($user)->postJson(route('api.v1.rooms.store'), [
            'property_id' => $propertyId,
            'room_type_id' => $roomType->id,
            'room_number' => '801',
            'status' => 'available',
            'cleaning_status' => 'clean',
        ])->json('data.id');

        $roomTwo = $this->actingAs($user)->postJson(route('api.v1.rooms.store'), [
            'property_id' => $propertyId,
            'room_type_id' => $roomType->id,
            'room_number' => '802',
            'status' => 'available',
            'cleaning_status' => 'clean',
        ])->json('data.id');

        $reservationResponse = $this->actingAs($user)->postJson(route('api.v1.reservations.store'), [
            'property_id' => $propertyId,
            'room_id' => $roomOne,
            'guest_profile_id' => $guestId,
            'booking_source' => 'walk_in',
            'currency_code' => 'USD',
            'status' => Reservation::STATUS_CONFIRMED,
            'check_in_date' => '2026-07-01',
            'check_out_date' => '2026-07-03',
            'adult_count' => 1,
            'rate_amount' => 600,
            'tax_amount' => 60,
            'total_amount' => 660,
        ]);

        $reservationResponse->assertCreated();
        $reservationId = $reservationResponse->json('data.id');

        $checkInResponse = $this->actingAs($user)->post(route('api.v1.reservations.check-in', $reservationId), [
            'signature_name' => 'Linus Torvalds',
            'signature_file' => UploadedFile::fake()->image('checkin-signature.png'),
            'identity_documents' => [
                [
                    'document_type' => 'passport',
                    'document_number' => 'P-44332211',
                    'issuing_country' => 'FI',
                    'expires_at' => '2030-07-03',
                    'file' => UploadedFile::fake()->image('passport.png'),
                    'is_primary' => true,
                    'notes' => 'Captured at front desk check-in.',
                ],
            ],
            'visitors' => [
                [
                    'full_name' => 'Tove Torvalds',
                    'relationship_to_guest' => 'spouse',
                    'identification_number' => 'VIS-1001',
                    'phone' => '+358401234567',
                    'notes' => 'Authorized accompanying visitor.',
                ],
            ],
        ], [
            'Accept' => 'application/json',
        ]);

        $checkInResponse->assertOk();
        $checkInResponse->assertJsonPath('data.status', Reservation::STATUS_CHECKED_IN);
        $checkInResponse->assertJsonPath('data.check_in_signature_name', 'Linus Torvalds');
        $checkInResponse->assertJsonPath('data.identity_documents.0.document_type', 'passport');
        $checkInResponse->assertJsonPath('data.visitors.0.full_name', 'Tove Torvalds');
        $this->assertDatabaseHas('hotel_rooms', ['id' => $roomOne, 'status' => Room::STATUS_OCCUPIED]);
        $this->assertDatabaseHas('hotel_folios', ['reservation_id' => $reservationId]);
        $this->assertDatabaseHas('hotel_guest_identity_documents', [
            'reservation_id' => $reservationId,
            'guest_profile_id' => $guestId,
            'document_type' => 'passport',
            'document_number' => 'P-44332211',
        ]);
        $this->assertDatabaseHas('hotel_guest_profiles', [
            'id' => $guestId,
            'passport_number' => 'P-44332211',
        ]);
        $this->assertDatabaseHas('hotel_reservation_visitors', [
            'reservation_id' => $reservationId,
            'full_name' => 'Tove Torvalds',
            'identification_number' => 'VIS-1001',
        ]);

        $checkedInReservation = Reservation::query()->findOrFail($reservationId);
        Storage::disk('public')->assertExists($checkedInReservation->check_in_signature_path);

        $moveResponse = $this->actingAs($user)->postJson(route('api.v1.reservations.move-room', $reservationId), [
            'to_room_id' => $roomTwo,
            'reason' => 'Guest requested quieter room',
        ]);

        $moveResponse->assertOk();
        $moveResponse->assertJsonPath('data.room_id', $roomTwo);
        $this->assertDatabaseHas('hotel_room_moves', [
            'reservation_id' => $reservationId,
            'from_room_id' => $roomOne,
            'to_room_id' => $roomTwo,
        ]);

        $checkoutResponse = $this->actingAs($user)->postJson(route('api.v1.reservations.check-out', $reservationId), [
            'housekeeping_notes' => 'Prioritize linen replacement.',
        ]);

        $checkoutResponse->assertOk();
        $checkoutResponse->assertJsonPath('data.status', Reservation::STATUS_CHECKED_OUT);
        $this->assertDatabaseHas('hotel_rooms', ['id' => $roomTwo, 'status' => Room::STATUS_DIRTY, 'cleaning_status' => 'dirty']);

        $task = HousekeepingTask::query()->firstOrFail();
        $this->assertSame(HousekeepingTask::TYPE_CHECKOUT_CLEANING, $task->task_type);

        $taskUpdateResponse = $this->actingAs($user)->patchJson(route('api.v1.housekeeping-tasks.update', $task), [
            'property_id' => $propertyId,
            'room_id' => $roomTwo,
            'reservation_id' => $reservationId,
            'assigned_to_user_id' => $technician->id,
            'task_type' => $task->task_type,
            'status' => HousekeepingTask::STATUS_INSPECTED,
            'priority' => 'high',
            'linen_status' => HousekeepingTask::LINEN_STATUS_COMPLETED,
            'linen_items_collected' => 4,
            'linen_items_delivered' => 4,
            'minibar_status' => HousekeepingTask::MINIBAR_STATUS_RESTOCKED,
            'minibar_charge_amount' => 18.50,
            'inspection_status' => HousekeepingTask::INSPECTION_STATUS_PASSED,
            'inspected_by_user_id' => $user->id,
            'inspection_notes' => 'Room cleared after final inspection.',
        ]);

        $taskUpdateResponse->assertOk();
        $taskUpdateResponse->assertJsonPath('data.status', HousekeepingTask::STATUS_INSPECTED);
        $taskUpdateResponse->assertJsonPath('data.assigned_to_user_id', $technician->id);
        $taskUpdateResponse->assertJsonPath('data.linen_items_collected', 4);
        $taskUpdateResponse->assertJsonPath('data.minibar_status', HousekeepingTask::MINIBAR_STATUS_RESTOCKED);
        $this->assertDatabaseHas('hotel_rooms', ['id' => $roomTwo, 'status' => Room::STATUS_AVAILABLE, 'cleaning_status' => 'clean']);
        $this->assertDatabaseHas('hotel_room_inspections', [
            'housekeeping_task_id' => $task->id,
            'room_id' => $roomTwo,
            'status' => RoomInspection::STATUS_PASSED,
        ]);

        $scheduleResponse = $this->actingAs($user)->postJson(route('api.v1.preventive-maintenance-schedules.store'), [
            'property_id' => $propertyId,
            'room_id' => $roomTwo,
            'assigned_to_user_id' => $technician->id,
            'title' => 'Quarterly HVAC preventive service',
            'description' => 'Inspect filters and condensate lines.',
            'maintenance_category' => 'hvac',
            'priority' => MaintenanceRequest::PRIORITY_MEDIUM,
            'frequency_days' => 90,
            'next_due_at' => '2026-07-05 09:00:00',
            'is_active' => true,
        ]);

        $scheduleResponse->assertOk();
        $scheduleId = $scheduleResponse->json('data.id');

        $generatedMaintenanceResponse = $this->actingAs($user)->postJson(route('api.v1.preventive-maintenance-schedules.generate', $scheduleId));

        $generatedMaintenanceResponse->assertOk();
        $generatedMaintenanceResponse->assertJsonPath('data.is_preventive', true);
        $generatedMaintenanceResponse->assertJsonPath('data.assigned_to_user_id', $technician->id);
        $generatedMaintenanceId = $generatedMaintenanceResponse->json('data.id');
        $this->assertDatabaseHas('hotel_rooms', ['id' => $roomTwo, 'status' => Room::STATUS_MAINTENANCE]);

        $maintenanceUpdateResponse = $this->actingAs($user)->patchJson(route('api.v1.maintenance-requests.update', $generatedMaintenanceId), [
            'property_id' => $propertyId,
            'room_id' => $roomTwo,
            'reported_by_user_id' => $user->id,
            'assigned_to_user_id' => $technician->id,
            'title' => 'Quarterly HVAC preventive service',
            'description' => 'Resolved by replacing drain line.',
            'maintenance_category' => 'hvac',
            'priority' => MaintenanceRequest::PRIORITY_MEDIUM,
            'is_preventive' => true,
            'preventive_maintenance_schedule_id' => $scheduleId,
            'status' => MaintenanceRequest::STATUS_COMPLETED,
            'technician_notes' => 'Drain line flushed and filters replaced.',
        ]);

        $maintenanceUpdateResponse->assertOk();
        $maintenanceUpdateResponse->assertJsonPath('data.status', MaintenanceRequest::STATUS_COMPLETED);
        $this->assertDatabaseHas('hotel_rooms', ['id' => $roomTwo, 'status' => Room::STATUS_DIRTY]);
        $this->assertDatabaseHas('hotel_preventive_maintenance_schedules', [
            'id' => $scheduleId,
            'assigned_to_user_id' => $technician->id,
        ]);

        $schedule = PreventiveMaintenanceSchedule::query()->findOrFail($scheduleId);
        $this->assertNotNull($schedule->last_generated_at);
        $this->assertNotNull($schedule->next_due_at);

        $this->assertDatabaseCount('hotel_room_moves', 1);
        $this->assertDatabaseCount('hotel_housekeeping_tasks', 1);
        $this->assertDatabaseCount('hotel_maintenance_requests', 1);
        $this->assertDatabaseCount('hotel_room_inspections', 1);
        $this->assertDatabaseCount('hotel_preventive_maintenance_schedules', 1);
    }

    protected function createTenantApiUser(Company $company, array $permissionNames): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);

        $role = Role::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Ops API Operator '.$company->id.'-'.str()->random(5),
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