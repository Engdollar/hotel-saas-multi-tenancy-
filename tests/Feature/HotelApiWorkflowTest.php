<?php

namespace Tests\Feature;

use App\Domain\Accounting\Models\Invoice;
use App\Domain\Accounting\Models\JournalEntry;
use App\Domain\Accounting\Models\SupplierBill;
use App\Domain\Hotel\Models\Property;
use App\Domain\Hotel\Models\RoomType;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HotelApiWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_hotel_api_crud_and_ar_ap_workflows_execute_with_tenant_scope(): void
    {
        $company = Company::query()->create(['name' => 'Atlas Hotel Group', 'status' => Company::STATUS_ACTIVE]);
        $user = $this->createTenantApiUser($company, [
            'create-property', 'read-property', 'show-property', 'update-property', 'delete-property',
            'create-room', 'read-room', 'show-room', 'update-room', 'delete-room',
            'create-guest', 'read-guest', 'show-guest', 'update-guest', 'delete-guest',
            'create-reservation', 'read-reservation', 'show-reservation', 'update-reservation', 'delete-reservation',
            'create-folio', 'read-folio', 'show-folio', 'update-folio',
            'create-invoice', 'read-invoice', 'show-invoice',
            'create-payment', 'create-refund',
            'create-supplier', 'read-supplier', 'show-supplier', 'update-supplier', 'delete-supplier',
            'create-supplier-bill', 'read-supplier-bill', 'show-supplier-bill',
            'create-supplier-payment',
        ]);

        $propertyResponse = $this->actingAs($user)->postJson(route('api.v1.properties.store'), [
            'branch_code' => 'ATL-HQ',
            'name' => 'Atlas Central Hotel',
            'property_type' => 'hotel',
            'timezone' => 'UTC',
            'currency_code' => 'USD',
            'check_in_time' => '14:00',
            'check_out_time' => '12:00',
            'status' => 'active',
        ]);

        $propertyResponse->assertCreated();
        $propertyId = $propertyResponse->json('data.id');

        app(CurrentCompanyContext::class)->set($company->id);

        $roomType = RoomType::query()->create([
            'property_id' => $propertyId,
            'name' => 'Deluxe Suite',
            'code' => 'DLX',
            'base_rate' => 250,
            'capacity_adults' => 2,
            'capacity_children' => 1,
            'status' => 'active',
        ]);

        $guestResponse = $this->actingAs($user)->postJson(route('api.v1.guests.store'), [
            'first_name' => 'Grace',
            'last_name' => 'Hopper',
            'email' => 'grace@example.com',
            'phone' => '+1234567',
            'nationality' => 'US',
            'is_vip' => true,
        ]);

        $guestResponse->assertCreated();
        $guestId = $guestResponse->json('data.id');

        $roomResponse = $this->actingAs($user)->postJson(route('api.v1.rooms.store'), [
            'property_id' => $propertyId,
            'room_type_id' => $roomType->id,
            'floor_label' => '7',
            'room_number' => '701',
            'status' => 'available',
            'cleaning_status' => 'clean',
        ]);

        $roomResponse->assertCreated();
        $roomId = $roomResponse->json('data.id');

        $reservationResponse = $this->actingAs($user)->postJson(route('api.v1.reservations.store'), [
            'property_id' => $propertyId,
            'room_id' => $roomId,
            'guest_profile_id' => $guestId,
            'booking_source' => 'online',
            'currency_code' => 'USD',
            'status' => 'confirmed',
            'check_in_date' => '2026-06-01',
            'check_out_date' => '2026-06-03',
            'adult_count' => 2,
            'child_count' => 0,
            'rate_amount' => 500,
            'tax_amount' => 50,
            'total_amount' => 550,
        ]);

        $reservationResponse->assertCreated();
        $reservationId = $reservationResponse->json('data.id');

        $folioResponse = $this->actingAs($user)->postJson(route('api.v1.folios.store'), [
            'reservation_id' => $reservationId,
        ]);

        $folioResponse->assertCreated();
        $folioId = $folioResponse->json('data.id');

        $chargeResponse = $this->actingAs($user)->postJson(route('api.v1.folios.charges.store', $folioId), [
            'line_type' => 'minibar',
            'description' => 'Minibar consumption',
            'quantity' => 2,
            'unit_price' => 20,
            'tax_amount' => 4,
            'total_amount' => 44,
            'service_date' => '2026-06-02',
        ]);

        $chargeResponse->assertOk();
        $chargeResponse->assertJsonPath('data.total_amount', '44.00');

        $invoiceResponse = $this->actingAs($user)->postJson(route('api.v1.invoices.store'), [
            'folio_id' => $folioId,
            'issue_date' => '2026-06-03',
            'due_date' => '2026-06-03',
        ]);

        $invoiceResponse->assertCreated();
        $invoiceId = $invoiceResponse->json('data.id');
        $invoiceNumber = $invoiceResponse->json('data.invoice_number');

        $paymentResponse = $this->actingAs($user)->postJson(route('api.v1.invoices.payments.store', $invoiceId), [
            'payment_method' => 'cash',
            'amount' => 44,
            'reference' => 'FRONTDESK-001',
        ]);

        $paymentResponse->assertOk();
        $paymentResponse->assertJsonPath('data.status', Invoice::STATUS_PAID);

        $refundResponse = $this->actingAs($user)->postJson(route('api.v1.invoices.refunds.store', $invoiceId), [
            'amount' => 10,
            'reason' => 'Goodwill adjustment',
        ]);

        $refundResponse->assertOk();
        $refundResponse->assertJsonPath('data.status', Invoice::STATUS_REFUNDED);

        $supplierResponse = $this->actingAs($user)->postJson(route('api.v1.suppliers.store'), [
            'name' => 'Ocean Foods',
            'email' => 'ap@oceanfoods.test',
            'status' => 'active',
        ]);

        $supplierResponse->assertCreated();
        $supplierId = $supplierResponse->json('data.id');

        $billResponse = $this->actingAs($user)->postJson(route('api.v1.supplier-bills.store'), [
            'supplier_id' => $supplierId,
            'currency_code' => 'USD',
            'bill_date' => '2026-06-04',
            'due_date' => '2026-06-10',
            'description' => 'Kitchen stock replenishment',
            'lines' => [
                [
                    'description' => 'Produce batch',
                    'quantity' => 1,
                    'unit_cost' => 120,
                    'tax_amount' => 12,
                    'total_amount' => 132,
                ],
            ],
        ]);

        $billResponse->assertCreated();
        $billId = $billResponse->json('data.id');

        $supplierPaymentResponse = $this->actingAs($user)->postJson(route('api.v1.supplier-bills.payments.store', $billId), [
            'payment_method' => 'bank_transfer',
            'amount' => 132,
            'reference' => 'BANK-332',
        ]);

        $supplierPaymentResponse->assertOk();
        $supplierPaymentResponse->assertJsonPath('data.status', SupplierBill::STATUS_PAID);

        $this->assertDatabaseHas('accounting_invoices', [
            'id' => $invoiceId,
            'invoice_number' => $invoiceNumber,
        ]);

        $this->assertDatabaseCount('accounting_journal_entries', 5);
        $this->assertTrue(JournalEntry::query()->where('description', 'like', 'Invoice payment for %')->exists());
        $this->assertTrue(JournalEntry::query()->where('description', 'like', 'Invoice refund for %')->exists());
        $this->assertTrue(JournalEntry::query()->where('description', 'like', 'Supplier bill %')->exists());
        $this->assertTrue(JournalEntry::query()->where('description', 'like', 'Supplier payment for %')->exists());
    }

    public function test_property_api_list_is_tenant_isolated(): void
    {
        $companyA = Company::query()->create(['name' => 'Alpha Hotels', 'status' => Company::STATUS_ACTIVE]);
        $companyB = Company::query()->create(['name' => 'Beta Hotels', 'status' => Company::STATUS_ACTIVE]);

        $user = $this->createTenantApiUser($companyA, ['read-property']);

        app(CurrentCompanyContext::class)->set($companyA->id);
        Property::query()->create([
            'branch_code' => 'ALPHA',
            'name' => 'Alpha Downtown',
            'property_type' => 'hotel',
            'timezone' => 'UTC',
            'currency_code' => 'USD',
        ]);

        app(CurrentCompanyContext::class)->set($companyB->id);
        Property::query()->create([
            'branch_code' => 'BETA',
            'name' => 'Beta Resort',
            'property_type' => 'resort',
            'timezone' => 'UTC',
            'currency_code' => 'USD',
        ]);

        $response = $this->actingAs($user)->getJson(route('api.v1.properties.index'));

        $response->assertOk();
        $response->assertSee('Alpha Downtown');
        $response->assertDontSee('Beta Resort');
    }

    protected function createTenantApiUser(Company $company, array $permissionNames): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);

        $role = Role::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'API Operator '.$company->id.'-'.str()->random(5),
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