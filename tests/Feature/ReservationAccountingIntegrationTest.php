<?php

namespace Tests\Feature;

use App\Domain\Accounting\Models\JournalEntry;
use App\Domain\Billing\Models\TenantSubscription;
use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Hotel\Models\GuestProfile;
use App\Domain\Hotel\Models\Property;
use App\Domain\Hotel\Models\Reservation;
use App\Domain\Hotel\Models\Room;
use App\Domain\Hotel\Models\RoomType;
use App\Domain\Hotel\Services\ReservationService;
use App\Models\Company;
use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationAccountingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmed_reservation_posts_balanced_journal_entry(): void
    {
        $company = Company::query()->create(['name' => 'Ledger Hotel Group', 'status' => Company::STATUS_ACTIVE]);
        app(CurrentCompanyContext::class)->set($company->id);

        $property = Property::query()->create([
            'branch_code' => 'LEDGER-HQ',
            'name' => 'Ledger Hotel',
            'property_type' => 'hotel',
            'timezone' => 'UTC',
            'currency_code' => 'USD',
        ]);

        $roomType = RoomType::query()->create([
            'property_id' => $property->id,
            'name' => 'Executive Suite',
            'code' => 'EXEC',
            'base_rate' => 250,
        ]);

        $room = Room::query()->create([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'room_number' => '701',
        ]);

        $guest = GuestProfile::query()->create([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
        ]);

        $reservation = app(ReservationService::class)->create([
            'property_id' => $property->id,
            'room_id' => $room->id,
            'guest_profile_id' => $guest->id,
            'status' => Reservation::STATUS_CONFIRMED,
            'booking_source' => 'online',
            'check_in_date' => '2026-05-20',
            'check_out_date' => '2026-05-22',
            'adult_count' => 2,
            'rate_amount' => 500,
            'tax_amount' => 50,
            'total_amount' => 550,
            'currency_code' => 'USD',
        ]);

        $entry = JournalEntry::query()
            ->where('source_type', Reservation::class)
            ->where('source_id', $reservation->id)
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame(JournalEntry::STATUS_POSTED, $entry->status);
        $this->assertCount(2, $entry->lines);
        $this->assertSame('550.00', number_format($entry->lines->sum('debit_amount'), 2, '.', ''));
        $this->assertSame('550.00', number_format($entry->lines->sum('credit_amount'), 2, '.', ''));
    }

    public function test_tenant_subscription_is_company_scoped(): void
    {
        $companyA = Company::query()->create(['name' => 'Alpha Hotel Group', 'status' => Company::STATUS_ACTIVE]);
        $companyB = Company::query()->create(['name' => 'Beta Hotel Group', 'status' => Company::STATUS_ACTIVE]);

        $plan = SubscriptionPlan::query()->create([
            'code' => 'starter',
            'name' => 'Starter',
            'monthly_price' => 99,
            'yearly_price' => 990,
            'currency_code' => 'USD',
            'max_properties' => 1,
            'max_users' => 15,
            'max_storage_gb' => 20,
            'features' => ['reservations'],
        ]);

        app(CurrentCompanyContext::class)->set($companyA->id);
        TenantSubscription::query()->create([
            'subscription_plan_id' => $plan->id,
            'status' => TenantSubscription::STATUS_ACTIVE,
            'billing_cycle' => 'monthly',
            'starts_at' => now(),
        ]);

        app(CurrentCompanyContext::class)->set($companyB->id);
        TenantSubscription::query()->create([
            'subscription_plan_id' => $plan->id,
            'status' => TenantSubscription::STATUS_TRIAL,
            'billing_cycle' => 'monthly',
            'starts_at' => now(),
        ]);

        app(CurrentCompanyContext::class)->set($companyA->id);

        $this->assertCount(1, TenantSubscription::query()->get());
        $this->assertSame(TenantSubscription::STATUS_ACTIVE, TenantSubscription::query()->firstOrFail()->status);
    }
}