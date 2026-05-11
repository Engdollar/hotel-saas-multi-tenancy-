<?php

namespace Tests\Feature;

use App\Domain\Inventory\Models\InventoryMovement;
use App\Domain\Hotel\Models\PosCashierShift;
use App\Domain\Hotel\Models\Reservation;
use App\Domain\Hotel\Models\PosOrder;
use App\Domain\Hotel\Models\PosOrderLine;
use App\Domain\Hotel\Models\RoomType;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_orders_post_room_charges_and_cashier_shift_reconciliation(): void
    {
        $company = Company::query()->create(['name' => 'Summit Hotels', 'status' => Company::STATUS_ACTIVE]);
        $cashier = User::factory()->create(['company_id' => $company->id]);
        $user = $this->createTenantApiUser($company, [
            'create-property', 'read-property', 'show-property',
            'create-room', 'read-room', 'show-room',
            'create-guest', 'read-guest', 'show-guest',
            'create-reservation', 'read-reservation', 'show-reservation',
            'create-folio', 'read-folio', 'show-folio', 'update-folio',
            'create-inventory-item', 'read-inventory-item', 'show-inventory-item',
            'create-pos-order', 'read-pos-order', 'show-pos-order',
            'create-pos-cashier-shift', 'read-pos-cashier-shift', 'show-pos-cashier-shift', 'update-pos-cashier-shift',
        ]);

        $propertyId = $this->actingAs($user)->postJson(route('api.v1.properties.store'), [
            'branch_code' => 'SUM-HQ',
            'name' => 'Summit Central',
            'property_type' => 'hotel',
            'timezone' => 'UTC',
            'currency_code' => 'USD',
            'status' => 'active',
        ])->json('data.id');

        app(CurrentCompanyContext::class)->set($company->id);

        $roomType = RoomType::query()->create([
            'property_id' => $propertyId,
            'name' => 'Suite',
            'code' => 'STE',
            'base_rate' => 280,
            'capacity_adults' => 2,
            'capacity_children' => 0,
            'status' => 'active',
        ]);

        $guestId = $this->actingAs($user)->postJson(route('api.v1.guests.store'), [
            'first_name' => 'Katherine',
            'last_name' => 'Johnson',
            'email' => 'katherine@example.com',
        ])->json('data.id');

        $roomId = $this->actingAs($user)->postJson(route('api.v1.rooms.store'), [
            'property_id' => $propertyId,
            'room_type_id' => $roomType->id,
            'room_number' => '901',
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
            'check_in_date' => '2026-09-01',
            'check_out_date' => '2026-09-03',
            'adult_count' => 1,
            'rate_amount' => 560,
            'tax_amount' => 56,
            'total_amount' => 616,
        ])->json('data.id');

        $folioId = $this->actingAs($user)->postJson(route('api.v1.folios.store'), [
            'reservation_id' => $reservationId,
        ])->json('data.id');

        $inventoryItemId = $this->actingAs($user)->postJson(route('api.v1.inventory-items.store'), [
            'property_id' => $propertyId,
            'sku' => 'INV-COFFEE-LOBBY',
            'name' => 'Lobby Coffee Beans',
            'category' => 'beverage',
            'unit_of_measure' => 'cup',
            'current_quantity' => 20,
            'reorder_level' => 5,
            'par_level' => 30,
            'unit_cost' => 2,
        ])->json('data.id');

        $shiftResponse = $this->actingAs($user)->postJson(route('api.v1.pos-cashier-shifts.store'), [
            'property_id' => $propertyId,
            'user_id' => $cashier->id,
            'opening_cash_amount' => 100,
        ]);

        $shiftResponse->assertCreated();
        $shiftId = $shiftResponse->json('data.id');

        $roomChargeResponse = $this->actingAs($user)->postJson(route('api.v1.pos-orders.store'), [
            'property_id' => $propertyId,
            'cashier_shift_id' => $shiftId,
            'reservation_id' => $reservationId,
            'folio_id' => $folioId,
            'payment_method' => 'room_charge',
            'service_location' => 'restaurant',
            'charge_to_room' => true,
            'lines' => [
                [
                    'item_name' => 'Dinner for one',
                    'category' => 'food',
                    'quantity' => 1,
                    'unit_price' => 45,
                    'tax_amount' => 5,
                    'total_amount' => 50,
                ],
            ],
        ]);

        $roomChargeResponse->assertOk();
        $roomChargeResponse->assertJsonPath('data.charge_to_room', true);
        $roomChargeResponse->assertJsonPath('data.posted_to_folio_at', fn ($value) => $value !== null);

        $cashOrderResponse = $this->actingAs($user)->postJson(route('api.v1.pos-orders.store'), [
            'property_id' => $propertyId,
            'cashier_shift_id' => $shiftId,
            'payment_method' => 'cash',
            'service_location' => 'bar',
            'charge_to_room' => false,
            'lines' => [
                [
                    'inventory_item_id' => $inventoryItemId,
                    'item_name' => 'Lobby coffee',
                    'category' => 'beverage',
                    'quantity' => 2,
                    'unit_price' => 6,
                    'tax_amount' => 0,
                    'total_amount' => 12,
                ],
            ],
        ]);

        $cashOrderResponse->assertOk();
        $cashOrderResponse->assertJsonPath('data.total_amount', '12.00');
        $cashOrderResponse->assertJsonPath('data.lines.0.inventory_item_id', $inventoryItemId);

        $folioResponse = $this->actingAs($user)->getJson(route('api.v1.folios.show', $folioId));
        $folioResponse->assertOk();
        $folioResponse->assertJsonPath('data.total_amount', '50.00');
        $folioResponse->assertJsonPath('data.lines.0.line_type', 'pos_charge');

        $inventoryResponse = $this->actingAs($user)->getJson(route('api.v1.inventory-items.show', $inventoryItemId));
        $inventoryResponse->assertOk();
        $inventoryResponse->assertJsonPath('data.current_quantity', '18.00');
        $inventoryResponse->assertJsonPath('data.movements.0.movement_type', InventoryMovement::TYPE_ISSUE);

        $closeResponse = $this->actingAs($user)->postJson(route('api.v1.pos-cashier-shifts.close', $shiftId), [
            'closing_cash_amount' => 112,
        ]);

        $closeResponse->assertOk();
        $closeResponse->assertJsonPath('data.status', PosCashierShift::STATUS_CLOSED);
        $closeResponse->assertJsonPath('data.expected_cash_amount', '112.00');
        $closeResponse->assertJsonPath('data.variance_amount', '0.00');

        $this->assertDatabaseHas('hotel_pos_orders', [
            'cashier_shift_id' => $shiftId,
            'folio_id' => $folioId,
            'payment_method' => 'room_charge',
        ]);
        $this->assertDatabaseHas('hotel_pos_cashier_shifts', [
            'id' => $shiftId,
            'status' => PosCashierShift::STATUS_CLOSED,
        ]);
        $this->assertDatabaseCount('hotel_pos_orders', 2);
        $this->assertDatabaseCount('hotel_pos_order_lines', 2);
        $this->assertDatabaseHas('hotel_pos_order_lines', [
            'inventory_item_id' => $inventoryItemId,
            'item_name' => 'Lobby coffee',
        ]);
    }

    public function test_pos_orders_support_modifiers_kitchen_routing_and_void_or_wastage_adjustments(): void
    {
        $company = Company::query()->create(['name' => 'Harbor Hotels', 'status' => Company::STATUS_ACTIVE]);
        $cashier = User::factory()->create(['company_id' => $company->id]);
        $user = $this->createTenantApiUser($company, [
            'create-property', 'read-property', 'show-property',
            'create-inventory-item', 'read-inventory-item', 'show-inventory-item',
            'create-pos-order', 'read-pos-order', 'show-pos-order', 'update-pos-order',
            'create-pos-cashier-shift', 'read-pos-cashier-shift', 'show-pos-cashier-shift',
        ]);

        $propertyId = $this->actingAs($user)->postJson(route('api.v1.properties.store'), [
            'branch_code' => 'HBR-HQ',
            'name' => 'Harbor Central',
            'property_type' => 'hotel',
            'timezone' => 'UTC',
            'currency_code' => 'USD',
            'status' => 'active',
        ])->json('data.id');

        $inventoryItemId = $this->actingAs($user)->postJson(route('api.v1.inventory-items.store'), [
            'property_id' => $propertyId,
            'sku' => 'INV-BURGER-001',
            'name' => 'Burger Patty',
            'category' => 'kitchen',
            'unit_of_measure' => 'unit',
            'current_quantity' => 20,
            'unit_cost' => 4,
        ])->json('data.id');

        $shiftId = $this->actingAs($user)->postJson(route('api.v1.pos-cashier-shifts.store'), [
            'property_id' => $propertyId,
            'user_id' => $cashier->id,
            'opening_cash_amount' => 50,
        ])->json('data.id');

        $orderResponse = $this->actingAs($user)->postJson(route('api.v1.pos-orders.store'), [
            'property_id' => $propertyId,
            'cashier_shift_id' => $shiftId,
            'payment_method' => 'cash',
            'service_location' => 'restaurant',
            'lines' => [[
                'inventory_item_id' => $inventoryItemId,
                'item_name' => 'Cheeseburger',
                'category' => 'food',
                'kitchen_station' => 'grill',
                'modifiers' => [
                    ['name' => 'Extra cheese', 'quantity' => 1, 'price' => 1.5],
                    ['name' => 'No onions', 'quantity' => 1, 'price' => 0],
                ],
                'quantity' => 2,
                'unit_price' => 12,
                'tax_amount' => 0,
            ]],
        ]);

        $orderResponse->assertOk();
        $orderId = $orderResponse->json('data.id');
        $lineId = $orderResponse->json('data.lines.0.id');
        $orderResponse->assertJsonPath('data.lines.0.modifier_total_amount', '1.50');
        $orderResponse->assertJsonPath('data.lines.0.kitchen_status', PosOrderLine::KITCHEN_STATUS_PENDING);

        $this->actingAs($user)->postJson(route('api.v1.pos-orders.send-to-kitchen', $orderId), [
            'line_ids' => [$lineId],
        ])->assertOk()->assertJsonPath('data.lines.0.kitchen_status', PosOrderLine::KITCHEN_STATUS_FIRED);

        $this->actingAs($user)->postJson(route('api.v1.pos-orders.mark-kitchen-ready', $orderId), [
            'line_ids' => [$lineId],
        ])->assertOk()->assertJsonPath('data.lines.0.kitchen_status', PosOrderLine::KITCHEN_STATUS_READY);

        $this->actingAs($user)->postJson(route('api.v1.pos-orders.wastage', $orderId), [
            'pos_order_line_id' => $lineId,
            'wasted_quantity' => 1,
            'reason' => 'Remake after kitchen mistake',
        ])->assertOk()->assertJsonPath('data.lines.0.wasted_quantity', '1.00');

        $voidOrderResponse = $this->actingAs($user)->postJson(route('api.v1.pos-orders.store'), [
            'property_id' => $propertyId,
            'cashier_shift_id' => $shiftId,
            'payment_method' => 'cash',
            'service_location' => 'restaurant',
            'lines' => [[
                'inventory_item_id' => $inventoryItemId,
                'item_name' => 'Burger to void',
                'kitchen_station' => 'grill',
                'quantity' => 1,
                'unit_price' => 10,
                'tax_amount' => 0,
            ]],
        ]);

        $voidOrderId = $voidOrderResponse->json('data.id');

        $voidResponse = $this->actingAs($user)->postJson(route('api.v1.pos-orders.void', $voidOrderId), [
            'reason' => 'Customer changed mind',
            'inventory_disposition' => 'restock',
        ]);

        $voidResponse->assertOk();
        $voidResponse->assertJsonPath('data.status', PosOrder::STATUS_VOID);
        $voidResponse->assertJsonPath('data.void_reason', 'Customer changed mind');

        $inventoryResponse = $this->actingAs($user)->getJson(route('api.v1.inventory-items.show', $inventoryItemId));
        $inventoryResponse->assertOk();
        $inventoryResponse->assertJsonPath('data.current_quantity', '17.00');

        $this->assertDatabaseHas('hotel_pos_orders', [
            'id' => $voidOrderId,
            'status' => PosOrder::STATUS_VOID,
            'void_reason' => 'Customer changed mind',
        ]);
        $this->assertDatabaseHas('hotel_pos_order_lines', [
            'id' => $lineId,
            'kitchen_status' => PosOrderLine::KITCHEN_STATUS_READY,
            'wastage_reason' => 'Remake after kitchen mistake',
        ]);
        $this->assertTrue(InventoryMovement::query()->where('movement_type', InventoryMovement::TYPE_WASTAGE)->exists());
        $this->assertTrue(InventoryMovement::query()->where('movement_type', InventoryMovement::TYPE_ADJUSTMENT)->exists());
    }

    protected function createTenantApiUser(Company $company, array $permissionNames): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);

        $role = Role::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'POS API Operator '.$company->id.'-'.str()->random(5),
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