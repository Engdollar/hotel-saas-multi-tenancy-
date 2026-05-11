<?php

namespace Tests\Feature;

use App\Domain\Accounting\Models\BankReconciliation;
use App\Domain\Accounting\Models\Invoice;
use App\Domain\Accounting\Models\Payment;
use App\Domain\Accounting\Models\SupplierBill;
use App\Domain\Accounting\Models\SupplierPayment;
use App\Domain\Hotel\Models\RoomType;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceControlsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_controls_cover_aging_bank_accounts_and_reconciliation(): void
    {
        $company = Company::query()->create(['name' => 'Harbor Hotels', 'status' => Company::STATUS_ACTIVE]);
        $user = $this->createTenantApiUser($company, [
            'create-property', 'read-property', 'show-property',
            'create-room', 'read-room', 'show-room',
            'create-guest', 'read-guest', 'show-guest',
            'create-reservation', 'read-reservation', 'show-reservation',
            'create-folio', 'show-folio', 'update-folio',
            'create-invoice', 'show-invoice', 'read-invoice',
            'create-payment', 'create-refund',
            'create-supplier', 'show-supplier', 'read-supplier',
            'create-supplier-bill', 'show-supplier-bill', 'read-supplier-bill',
            'create-supplier-payment',
            'create-bank-account', 'read-bank-account', 'show-bank-account', 'update-bank-account',
            'create-bank-reconciliation', 'read-bank-reconciliation', 'show-bank-reconciliation', 'update-bank-reconciliation',
            'read-report',
        ]);

        $propertyId = $this->actingAs($user)->postJson(route('api.v1.properties.store'), [
            'branch_code' => 'HRB-HQ',
            'name' => 'Harbor Central',
            'property_type' => 'hotel',
            'timezone' => 'UTC',
            'currency_code' => 'USD',
            'status' => 'active',
        ])->json('data.id');

        app(CurrentCompanyContext::class)->set($company->id);

        $roomType = RoomType::query()->create([
            'property_id' => $propertyId,
            'name' => 'Standard',
            'code' => 'STD',
            'base_rate' => 180,
            'capacity_adults' => 2,
            'capacity_children' => 0,
            'status' => 'active',
        ]);

        $guestId = $this->actingAs($user)->postJson(route('api.v1.guests.store'), [
            'first_name' => 'Mary',
            'last_name' => 'Jackson',
            'email' => 'mary@example.com',
        ])->json('data.id');

        $roomId = $this->actingAs($user)->postJson(route('api.v1.rooms.store'), [
            'property_id' => $propertyId,
            'room_type_id' => $roomType->id,
            'room_number' => '301',
            'status' => 'available',
            'cleaning_status' => 'clean',
        ])->json('data.id');

        $reservationId = $this->actingAs($user)->postJson(route('api.v1.reservations.store'), [
            'property_id' => $propertyId,
            'room_id' => $roomId,
            'guest_profile_id' => $guestId,
            'booking_source' => 'corporate',
            'currency_code' => 'USD',
            'status' => 'confirmed',
            'check_in_date' => '2026-03-01',
            'check_out_date' => '2026-03-02',
            'adult_count' => 1,
            'rate_amount' => 200,
            'tax_amount' => 20,
            'total_amount' => 220,
        ])->json('data.id');

        $folioId = $this->actingAs($user)->postJson(route('api.v1.folios.store'), [
            'reservation_id' => $reservationId,
        ])->json('data.id');

        $this->actingAs($user)->postJson(route('api.v1.folios.charges.store', $folioId), [
            'line_type' => 'room_charge',
            'description' => 'Nightly room charge',
            'quantity' => 1,
            'unit_price' => 220,
            'tax_amount' => 0,
            'total_amount' => 220,
            'service_date' => '2026-03-01',
        ])->assertOk();

        $invoiceId = $this->actingAs($user)->postJson(route('api.v1.invoices.store'), [
            'folio_id' => $folioId,
            'issue_date' => '2026-03-02',
            'due_date' => '2026-03-10',
        ])->json('data.id');

        $this->actingAs($user)->postJson(route('api.v1.invoices.payments.store', $invoiceId), [
            'payment_method' => 'bank_transfer',
            'amount' => 20,
            'reference' => 'AR-PMT-001',
            'paid_at' => '2026-03-15 10:00:00',
        ])->assertOk();

        $supplierId = $this->actingAs($user)->postJson(route('api.v1.suppliers.store'), [
            'name' => 'Linen Supply Co',
            'status' => 'active',
        ])->json('data.id');

        $billId = $this->actingAs($user)->postJson(route('api.v1.supplier-bills.store'), [
            'supplier_id' => $supplierId,
            'currency_code' => 'USD',
            'bill_date' => '2026-03-01',
            'due_date' => '2026-03-05',
            'description' => 'Laundry stock',
            'lines' => [
                [
                    'description' => 'Sheet order',
                    'quantity' => 1,
                    'unit_cost' => 100,
                    'tax_amount' => 0,
                    'total_amount' => 100,
                ],
            ],
        ])->json('data.id');

        $this->actingAs($user)->postJson(route('api.v1.supplier-bills.payments.store', $billId), [
            'payment_method' => 'bank_transfer',
            'amount' => 30,
            'reference' => 'AP-PMT-001',
            'paid_at' => '2026-03-06 14:00:00',
        ])->assertOk();

        $bankAccountResponse = $this->actingAs($user)->postJson(route('api.v1.bank-accounts.store'), [
            'name' => 'Operating Account',
            'bank_name' => 'Harbor Bank',
            'account_number_last4' => '4455',
            'currency_code' => 'USD',
            'current_balance' => 1200,
            'is_active' => true,
        ]);

        $bankAccountResponse->assertOk();
        $bankAccountId = $bankAccountResponse->json('data.id');

        $arAgingResponse = $this->actingAs($user)->getJson(route('api.v1.finance.ar-aging', ['as_of_date' => '2026-04-20']));
        $arAgingResponse->assertOk();
        $arAgingResponse->assertJsonPath('data.buckets.31_60', '200.00');

        $apAgingResponse = $this->actingAs($user)->getJson(route('api.v1.finance.ap-aging', ['as_of_date' => '2026-04-20']));
        $apAgingResponse->assertOk();
        $apAgingResponse->assertJsonPath('data.buckets.31_60', '70.00');

        $payment = Payment::query()->where('invoice_id', $invoiceId)->firstOrFail();
        $supplierPayment = SupplierPayment::query()->where('supplier_bill_id', $billId)->firstOrFail();

        $reconciliationResponse = $this->actingAs($user)->postJson(route('api.v1.bank-reconciliations.store'), [
            'bank_account_id' => $bankAccountId,
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-31',
            'statement_ending_balance' => 1190,
            'lines' => [
                [
                    'reference_type' => Payment::class,
                    'reference_id' => $payment->id,
                    'is_cleared' => true,
                ],
                [
                    'reference_type' => SupplierPayment::class,
                    'reference_id' => $supplierPayment->id,
                    'is_cleared' => true,
                ],
            ],
        ]);

        $reconciliationResponse->assertOk();
        $reconciliationId = $reconciliationResponse->json('data.id');
        $reconciliationResponse->assertJsonPath('data.cleared_balance', '-10.00');

        $completeResponse = $this->actingAs($user)->patchJson(route('api.v1.bank-reconciliations.update', $reconciliationId), [
            'bank_account_id' => $bankAccountId,
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-31',
            'statement_ending_balance' => 1190,
            'status' => BankReconciliation::STATUS_COMPLETED,
            'lines' => [
                [
                    'reference_type' => Payment::class,
                    'reference_id' => $payment->id,
                    'is_cleared' => true,
                ],
                [
                    'reference_type' => SupplierPayment::class,
                    'reference_id' => $supplierPayment->id,
                    'is_cleared' => true,
                ],
            ],
        ]);

        $completeResponse->assertOk();
        $completeResponse->assertJsonPath('data.status', BankReconciliation::STATUS_COMPLETED);

        $this->assertDatabaseHas('accounting_bank_accounts', ['id' => $bankAccountId, 'name' => 'Operating Account']);
        $this->assertDatabaseHas('accounting_bank_reconciliations', ['id' => $reconciliationId, 'status' => BankReconciliation::STATUS_COMPLETED]);
        $this->assertDatabaseCount('accounting_bank_reconciliation_lines', 2);
    }

    protected function createTenantApiUser(Company $company, array $permissionNames): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);

        $role = Role::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Finance API Operator '.$company->id.'-'.str()->random(5),
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