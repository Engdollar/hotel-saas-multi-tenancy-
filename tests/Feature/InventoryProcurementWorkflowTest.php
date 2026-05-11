<?php

namespace Tests\Feature;

use App\Domain\Accounting\Models\SupplierBill;
use App\Domain\Inventory\Models\InventoryMovement;
use App\Domain\Inventory\Models\PurchaseOrder;
use App\Domain\Inventory\Models\PurchaseOrderApproval;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryProcurementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_items_purchase_orders_and_goods_receipts_drive_replenishment(): void
    {
        $company = Company::query()->create(['name' => 'Orion Hotels', 'status' => Company::STATUS_ACTIVE]);
        $user = $this->createTenantApiUser($company, [
            'create-property', 'read-property', 'show-property',
            'create-supplier', 'read-supplier', 'show-supplier',
            'create-inventory-item', 'read-inventory-item', 'show-inventory-item', 'update-inventory-item',
            'create-purchase-order', 'read-purchase-order', 'show-purchase-order', 'update-purchase-order',
            'create-supplier-bill',
        ]);

        $propertyId = $this->actingAs($user)->postJson(route('api.v1.properties.store'), [
            'branch_code' => 'ORI-HQ',
            'name' => 'Orion Central',
            'property_type' => 'hotel',
            'timezone' => 'UTC',
            'currency_code' => 'USD',
            'status' => 'active',
        ])->json('data.id');

        $supplierId = $this->actingAs($user)->postJson(route('api.v1.suppliers.store'), [
            'name' => 'Fresh Kitchen Supply',
            'status' => 'active',
        ])->json('data.id');

        $itemResponse = $this->actingAs($user)->postJson(route('api.v1.inventory-items.store'), [
            'property_id' => $propertyId,
            'preferred_supplier_id' => $supplierId,
            'sku' => 'INV-COFFEE-001',
            'name' => 'Coffee Beans',
            'category' => 'beverage',
            'unit_of_measure' => 'kg',
            'reorder_level' => 5,
            'par_level' => 20,
            'unit_cost' => 8,
            'current_quantity' => 2,
        ]);

        $itemResponse->assertCreated();
        $itemId = $itemResponse->json('data.id');

        $orderResponse = $this->actingAs($user)->postJson(route('api.v1.purchase-orders.store'), [
            'property_id' => $propertyId,
            'supplier_id' => $supplierId,
            'currency_code' => 'USD',
            'order_date' => '2026-10-01',
            'expected_delivery_date' => '2026-10-03',
            'notes' => 'Replenish bar inventory',
            'lines' => [
                [
                    'inventory_item_id' => $itemId,
                    'description' => 'Coffee Beans 1kg bag',
                    'ordered_quantity' => 10,
                    'unit_cost' => 8,
                    'tax_amount' => 0,
                    'total_amount' => 80,
                ],
            ],
        ]);

        $orderResponse->assertCreated();
        $orderId = $orderResponse->json('data.id');
        $orderLineId = $orderResponse->json('data.lines.0.id');
        $orderResponse->assertJsonPath('data.status', PurchaseOrder::STATUS_DRAFT);

        $approveResponse = $this->actingAs($user)->postJson(route('api.v1.purchase-orders.approve', $orderId));
        $approveResponse->assertOk();
        $approveResponse->assertJsonPath('data.status', PurchaseOrder::STATUS_APPROVED);
        $approveResponse->assertJsonPath('data.approved_by_user_id', $user->id);

        $receiptResponse = $this->actingAs($user)->postJson(route('api.v1.purchase-orders.receive', $orderId), [
            'received_at' => '2026-10-02 11:00:00',
            'notes' => 'Delivered to kitchen storage',
            'lines' => [
                [
                    'purchase_order_line_id' => $orderLineId,
                    'received_quantity' => 10,
                    'unit_cost' => 8,
                ],
            ],
        ]);

        $receiptResponse->assertOk();
        $receiptResponse->assertJsonPath('data.status', PurchaseOrder::STATUS_RECEIVED);
        $receiptResponse->assertJsonPath('data.receipts.0.lines.0.received_quantity', '10.00');

        $billResponse = $this->actingAs($user)->postJson(route('api.v1.supplier-bills.store'), [
            'supplier_id' => $supplierId,
            'purchase_order_id' => $orderId,
            'currency_code' => 'USD',
            'bill_date' => '2026-10-02',
            'due_date' => '2026-10-09',
            'description' => 'Matched supplier invoice',
            'lines' => [
                [
                    'purchase_order_line_id' => $orderLineId,
                    'inventory_item_id' => $itemId,
                    'description' => 'Coffee Beans 1kg bag',
                    'quantity' => 10,
                    'unit_cost' => 8,
                    'tax_amount' => 0,
                    'total_amount' => 80,
                ],
            ],
        ]);

        $billResponse->assertCreated();
        $billResponse->assertJsonPath('data.purchase_order_id', $orderId);
        $billResponse->assertJsonPath('data.match_status', SupplierBill::MATCH_STATUS_MATCHED);
        $billResponse->assertJsonPath('data.lines.0.purchase_order_line_id', $orderLineId);

        $itemShowResponse = $this->actingAs($user)->getJson(route('api.v1.inventory-items.show', $itemId));
        $itemShowResponse->assertOk();
        $itemShowResponse->assertJsonPath('data.current_quantity', '12.00');
        $itemShowResponse->assertJsonPath('data.movements.0.movement_type', InventoryMovement::TYPE_RECEIPT);

        $this->assertDatabaseHas('procurement_purchase_orders', [
            'id' => $orderId,
            'supplier_id' => $supplierId,
            'status' => PurchaseOrder::STATUS_RECEIVED,
            'approved_by_user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('procurement_purchase_order_lines', [
            'id' => $orderLineId,
            'billed_quantity' => 10,
        ]);
        $this->assertDatabaseHas('inventory_items', [
            'id' => $itemId,
            'preferred_supplier_id' => $supplierId,
        ]);
        $this->assertDatabaseCount('inventory_movements', 1);
        $this->assertDatabaseCount('procurement_goods_receipts', 1);
        $this->assertDatabaseCount('accounting_supplier_bills', 1);
    }

    public function test_purchase_orders_support_multistep_approval_and_within_tolerance_three_way_matching(): void
    {
        $company = Company::query()->create(['name' => 'Atlas Hospitality', 'status' => Company::STATUS_ACTIVE]);
        $requester = $this->createTenantApiUser($company, [
            'create-property', 'read-property', 'show-property',
            'create-supplier', 'read-supplier', 'show-supplier',
            'create-inventory-item', 'read-inventory-item', 'show-inventory-item',
            'create-purchase-order', 'read-purchase-order', 'show-purchase-order', 'update-purchase-order',
            'create-supplier-bill',
        ]);
        $approverOne = $this->createTenantApiUser($company, ['read-purchase-order', 'show-purchase-order', 'update-purchase-order']);
        $approverTwo = $this->createTenantApiUser($company, ['read-purchase-order', 'show-purchase-order', 'update-purchase-order']);

        $propertyId = $this->actingAs($requester)->postJson(route('api.v1.properties.store'), [
            'branch_code' => 'ATL-HQ',
            'name' => 'Atlas Central',
            'property_type' => 'hotel',
            'timezone' => 'UTC',
            'currency_code' => 'USD',
            'status' => 'active',
        ])->json('data.id');

        $supplierId = $this->actingAs($requester)->postJson(route('api.v1.suppliers.store'), [
            'name' => 'Prime Produce',
            'status' => 'active',
        ])->json('data.id');

        $itemId = $this->actingAs($requester)->postJson(route('api.v1.inventory-items.store'), [
            'property_id' => $propertyId,
            'preferred_supplier_id' => $supplierId,
            'sku' => 'INV-LETTUCE-001',
            'name' => 'Romaine Lettuce',
            'category' => 'produce',
            'unit_of_measure' => 'case',
            'unit_cost' => 10,
            'current_quantity' => 1,
        ])->json('data.id');

        $orderResponse = $this->actingAs($requester)->postJson(route('api.v1.purchase-orders.store'), [
            'property_id' => $propertyId,
            'supplier_id' => $supplierId,
            'currency_code' => 'USD',
            'quantity_tolerance_percent' => 5,
            'amount_tolerance_percent' => 5,
            'approval_steps' => [
                ['approver_user_id' => $approverOne->id, 'notes' => 'Ops approval'],
                ['approver_user_id' => $approverTwo->id, 'notes' => 'Finance approval'],
            ],
            'lines' => [[
                'inventory_item_id' => $itemId,
                'description' => 'Romaine Lettuce case',
                'ordered_quantity' => 10,
                'unit_cost' => 10,
                'tax_amount' => 0,
                'total_amount' => 100,
            ]],
        ]);

        $orderResponse->assertCreated();
        $orderId = $orderResponse->json('data.id');
        $orderLineId = $orderResponse->json('data.lines.0.id');
        $orderResponse->assertJsonCount(2, 'data.approvals');

        $this->actingAs($approverOne)->postJson(route('api.v1.purchase-orders.approve', $orderId), [
            'notes' => 'Operations approved',
        ])->assertOk()->assertJsonPath('data.status', PurchaseOrder::STATUS_DRAFT);

        $secondApprovalResponse = $this->actingAs($approverTwo)->postJson(route('api.v1.purchase-orders.approve', $orderId), [
            'notes' => 'Finance approved',
        ]);

        $secondApprovalResponse->assertOk();
        $secondApprovalResponse->assertJsonPath('data.status', PurchaseOrder::STATUS_APPROVED);
        $secondApprovalResponse->assertJsonPath('data.approvals.0.status', PurchaseOrderApproval::STATUS_APPROVED);
        $secondApprovalResponse->assertJsonPath('data.approvals.1.status', PurchaseOrderApproval::STATUS_APPROVED);

        $this->actingAs($requester)->postJson(route('api.v1.purchase-orders.receive', $orderId), [
            'lines' => [[
                'purchase_order_line_id' => $orderLineId,
                'received_quantity' => 10,
                'unit_cost' => 10,
            ]],
        ])->assertOk();

        $billResponse = $this->actingAs($requester)->postJson(route('api.v1.supplier-bills.store'), [
            'supplier_id' => $supplierId,
            'purchase_order_id' => $orderId,
            'currency_code' => 'USD',
            'lines' => [[
                'purchase_order_line_id' => $orderLineId,
                'inventory_item_id' => $itemId,
                'description' => 'Romaine Lettuce case',
                'quantity' => 10.4,
                'unit_cost' => 10,
                'tax_amount' => 0,
                'total_amount' => 104,
            ]],
        ]);

        $billResponse->assertCreated();
        $billResponse->assertJsonPath('data.match_status', SupplierBill::MATCH_STATUS_WITHIN_TOLERANCE);

        $purchaseOrderResponse = $this->actingAs($requester)->getJson(route('api.v1.purchase-orders.show', $orderId));
        $purchaseOrderResponse->assertOk();
        $purchaseOrderResponse->assertJsonPath('data.match_status', PurchaseOrder::MATCH_STATUS_WITHIN_TOLERANCE);

        $this->assertDatabaseHas('procurement_purchase_orders', [
            'id' => $orderId,
            'status' => PurchaseOrder::STATUS_RECEIVED,
            'match_status' => PurchaseOrder::MATCH_STATUS_WITHIN_TOLERANCE,
        ]);
        $this->assertDatabaseCount('procurement_purchase_order_approvals', 2);
    }

    protected function createTenantApiUser(Company $company, array $permissionNames): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);

        $role = Role::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Inventory API Operator '.$company->id.'-'.str()->random(5),
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