<?php

namespace Tests\Feature;

use App\Domain\Accounting\Models\Invoice;
use App\Domain\Accounting\Models\BankAccount;
use App\Domain\Accounting\Models\BankReconciliation;
use App\Domain\Accounting\Models\Supplier;
use App\Domain\Accounting\Models\SupplierBill;
use App\Domain\Accounting\Models\Payment;
use App\Domain\Accounting\Models\Refund;
use App\Domain\Hotel\Models\Folio;
use App\Domain\Hotel\Models\FolioLine;
use App\Domain\Hotel\Models\HousekeepingTask;
use App\Domain\Hotel\Models\MaintenanceRequest;
use App\Domain\Hotel\Models\PosCashierShift;
use App\Domain\Hotel\Models\PosOrder;
use App\Domain\Hotel\Models\PreventiveMaintenanceSchedule;
use App\Domain\Hotel\Models\Property;
use App\Domain\Hotel\Models\Reservation;
use App\Domain\Hotel\Models\Room;
use App\Domain\Hotel\Models\RoomType;
use App\Domain\Inventory\Models\InventoryItem;
use App\Domain\Inventory\Models\InventoryMovement;
use App\Domain\Inventory\Models\PurchaseOrder;
use App\Domain\Inventory\Models\PurchaseOrderApproval;
use App\Domain\Inventory\Models\PurchaseOrderLine;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantWorkspaceUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_dashboard_and_sidebar_render_company_scoped_erp_content(): void
    {
        $companyA = Company::query()->create(['name' => 'Atlas Hotels', 'status' => Company::STATUS_ACTIVE]);
        $companyB = Company::query()->create(['name' => 'Beta Hotels', 'status' => Company::STATUS_ACTIVE]);

        $user = $this->createTenantUser($companyA, [
            'read-dashboard', 'read-property', 'read-room', 'read-guest', 'read-reservation', 'read-folio',
            'read-invoice', 'read-payment', 'read-refund', 'read-supplier-bill', 'read-supplier-payment',
            'read-report', 'read-bank-account', 'read-bank-reconciliation', 'read-housekeeping-task',
            'read-maintenance-request', 'read-preventive-maintenance-schedule', 'read-pos-cashier-shift',
            'read-pos-order', 'read-inventory-item', 'read-purchase-order', 'read-setting', 'read-ticket',
            'create-reservation', 'create-folio', 'create-supplier-bill', 'create-maintenance-request',
            'create-purchase-order', 'create-pos-order',
        ]);

        $property = Property::query()->create([
            'company_id' => $companyA->id,
            'branch_code' => 'ATL-DT',
            'name' => 'Atlas Downtown',
            'property_type' => 'hotel',
            'timezone' => 'UTC',
            'currency_code' => 'USD',
            'status' => 'active',
        ]);

        $roomType = RoomType::query()->create([
            'company_id' => $companyA->id,
            'property_id' => $property->id,
            'name' => 'Deluxe',
            'code' => 'DLX',
            'base_rate' => 180,
            'capacity_adults' => 2,
            'capacity_children' => 1,
            'status' => 'active',
        ]);

        $occupiedRoom = Room::query()->create([
            'company_id' => $companyA->id,
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'room_number' => '701',
            'floor_label' => '7',
            'status' => Room::STATUS_OCCUPIED,
            'cleaning_status' => 'clean',
        ]);

        Room::query()->create([
            'company_id' => $companyA->id,
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'room_number' => '702',
            'floor_label' => '7',
            'status' => Room::STATUS_AVAILABLE,
            'cleaning_status' => 'clean',
        ]);

        $guest = \App\Domain\Hotel\Models\GuestProfile::query()->create([
            'company_id' => $companyA->id,
            'first_name' => 'Grace',
            'last_name' => 'Hopper',
            'email' => 'grace@example.test',
        ]);

        $reservation = Reservation::query()->create([
            'company_id' => $companyA->id,
            'property_id' => $property->id,
            'room_id' => $occupiedRoom->id,
            'guest_profile_id' => $guest->id,
            'status' => Reservation::STATUS_CHECKED_IN,
            'currency_code' => 'USD',
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->copy()->addDay()->toDateString(),
            'adult_count' => 2,
            'child_count' => 0,
            'night_count' => 1,
            'rate_amount' => 180,
            'tax_amount' => 20,
            'total_amount' => 200,
        ]);

        $folio = Folio::query()->create([
            'company_id' => $companyA->id,
            'reservation_id' => $reservation->id,
            'guest_profile_id' => $guest->id,
            'status' => Folio::STATUS_OPEN,
            'currency_code' => 'USD',
            'subtotal_amount' => 180,
            'tax_amount' => 20,
            'total_amount' => 200,
            'balance_amount' => 200,
        ]);

        Invoice::query()->create([
            'company_id' => $companyA->id,
            'guest_profile_id' => $guest->id,
            'folio_id' => $folio->id,
            'status' => Invoice::STATUS_ISSUED,
            'currency_code' => 'USD',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->copy()->subDays(10)->toDateString(),
            'subtotal_amount' => 180,
            'tax_amount' => 20,
            'total_amount' => 200,
            'balance_amount' => 150,
        ]);

        $supplier = Supplier::query()->create([
            'company_id' => $companyA->id,
            'name' => 'Fresh Supply Co',
            'status' => 'active',
        ]);

        $lowStockA = InventoryItem::query()->create([
            'company_id' => $companyA->id,
            'property_id' => $property->id,
            'preferred_supplier_id' => $supplier->id,
            'sku' => 'INV-A-1',
            'name' => 'Tomatoes A',
            'category' => 'produce',
            'unit_of_measure' => 'kg',
            'current_quantity' => 2,
            'reorder_level' => 5,
            'par_level' => 10,
            'unit_cost' => 3.5,
            'is_active' => true,
        ]);

        InventoryItem::query()->create([
            'company_id' => $companyB->id,
            'sku' => 'INV-B-1',
            'name' => 'Hidden Beta Item',
            'category' => 'produce',
            'unit_of_measure' => 'kg',
            'current_quantity' => 1,
            'reorder_level' => 5,
            'par_level' => 10,
            'unit_cost' => 2.5,
            'is_active' => true,
        ]);

        $purchaseOrder = PurchaseOrder::query()->create([
            'company_id' => $companyA->id,
            'property_id' => $property->id,
            'supplier_id' => $supplier->id,
            'status' => PurchaseOrder::STATUS_DRAFT,
            'match_status' => PurchaseOrder::MATCH_STATUS_UNMATCHED,
            'currency_code' => 'USD',
            'order_date' => now()->toDateString(),
            'expected_delivery_date' => now()->copy()->addDay()->toDateString(),
            'subtotal_amount' => 100,
            'tax_amount' => 10,
            'total_amount' => 110,
        ]);

        PurchaseOrderApproval::query()->create([
            'company_id' => $companyA->id,
            'purchase_order_id' => $purchaseOrder->id,
            'sequence_number' => 1,
            'approver_user_id' => $user->id,
            'status' => PurchaseOrderApproval::STATUS_PENDING,
        ]);

        SupplierBill::query()->create([
            'company_id' => $companyA->id,
            'supplier_id' => $supplier->id,
            'purchase_order_id' => $purchaseOrder->id,
            'status' => SupplierBill::STATUS_APPROVED,
            'match_status' => SupplierBill::MATCH_STATUS_EXCEPTION,
            'currency_code' => 'USD',
            'bill_date' => now()->toDateString(),
            'due_date' => now()->copy()->addDays(7)->toDateString(),
            'subtotal_amount' => 100,
            'tax_amount' => 10,
            'total_amount' => 110,
            'balance_amount' => 110,
        ]);

        HousekeepingTask::query()->create([
            'company_id' => $companyA->id,
            'property_id' => $property->id,
            'room_id' => $occupiedRoom->id,
            'reservation_id' => $reservation->id,
            'task_type' => HousekeepingTask::TYPE_STAYOVER_CLEANING,
            'status' => HousekeepingTask::STATUS_PENDING,
            'priority' => 'high',
            'scheduled_for' => now(),
        ]);

        MaintenanceRequest::query()->create([
            'company_id' => $companyA->id,
            'property_id' => $property->id,
            'room_id' => $occupiedRoom->id,
            'reported_by_user_id' => $user->id,
            'title' => 'HVAC alarm',
            'priority' => MaintenanceRequest::PRIORITY_HIGH,
            'status' => MaintenanceRequest::STATUS_OPEN,
            'reported_at' => now(),
        ]);

        $shift = PosCashierShift::query()->create([
            'company_id' => $companyA->id,
            'property_id' => $property->id,
            'user_id' => $user->id,
            'status' => PosCashierShift::STATUS_OPEN,
            'opening_cash_amount' => 100,
        ]);

        PosOrder::query()->create([
            'company_id' => $companyA->id,
            'property_id' => $property->id,
            'cashier_shift_id' => $shift->id,
            'status' => PosOrder::STATUS_PAID,
            'payment_method' => 'cash',
            'service_location' => 'restaurant',
            'charge_to_room' => false,
            'paid_at' => now(),
            'subtotal_amount' => 12,
            'tax_amount' => 1.2,
            'total_amount' => 13.2,
        ]);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Tenant ERP Dashboard');
        $response->assertSee('Tenant Workspace');
        $response->assertSee('Front Desk');
        $response->assertSee('Finance');
        $response->assertSee('POS &amp; Inventory', false);
        $response->assertSee('Create reservation');
        $response->assertSee('Tomatoes A');
        $response->assertDontSee('Hidden Beta Item');
        $response->assertDontSee('Companies');
        $response->assertSee('Purchase Orders');
    }

    public function test_tenant_workspace_module_page_and_create_flow_work(): void
    {
        $company = Company::query()->create(['name' => 'Atlas Hotels', 'status' => Company::STATUS_ACTIVE]);
        $user = $this->createTenantUser($company, [
            'read-dashboard', 'read-maintenance-request', 'create-maintenance-request',
        ]);

        $property = Property::query()->create([
            'company_id' => $company->id,
            'branch_code' => 'ATL-HQ',
            'name' => 'Atlas HQ',
            'property_type' => 'hotel',
            'timezone' => 'UTC',
            'currency_code' => 'USD',
            'status' => 'active',
        ]);

        $indexResponse = $this->actingAs($user)->get(route('admin.workspace.modules.show', ['module' => 'maintenance-requests']));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Maintenance Requests');

        $createResponse = $this->actingAs($user)->get(route('admin.workspace.modules.create', ['module' => 'maintenance-requests']));
        $createResponse->assertOk();
        $createResponse->assertSee('Create Maintenance Requests');

        $storeResponse = $this->actingAs($user)->post(route('admin.workspace.modules.store', ['module' => 'maintenance-requests']), [
            'property_id' => $property->id,
            'title' => 'Lift alarm',
            'priority' => MaintenanceRequest::PRIORITY_URGENT,
        ]);

        $storeResponse->assertRedirect(route('admin.workspace.modules.show', ['module' => 'maintenance-requests']));
        $this->assertDatabaseHas('hotel_maintenance_requests', [
            'company_id' => $company->id,
            'title' => 'Lift alarm',
            'priority' => MaintenanceRequest::PRIORITY_URGENT,
        ]);
    }

    public function test_tenant_sidebar_keeps_module_active_for_record_and_edit_pages(): void
    {
        $company = Company::query()->create(['name' => 'Atlas Hotels', 'status' => Company::STATUS_ACTIVE]);
        $user = $this->createTenantUser($company, [
            'read-dashboard', 'read-maintenance-request', 'update-maintenance-request',
        ]);

        $property = Property::query()->create([
            'company_id' => $company->id,
            'branch_code' => 'ATL-ACTIVE',
            'name' => 'Atlas Active',
            'property_type' => 'hotel',
            'timezone' => 'UTC',
            'currency_code' => 'USD',
            'status' => 'active',
        ]);

        $request = MaintenanceRequest::query()->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'reported_by_user_id' => $user->id,
            'title' => 'Door sensor issue',
            'priority' => MaintenanceRequest::PRIORITY_HIGH,
            'status' => MaintenanceRequest::STATUS_OPEN,
            'reported_at' => now(),
        ]);

        $expectedRoute = 'href="'.route('admin.workspace.modules.show', ['module' => 'maintenance-requests']).'"';

        $showResponse = $this->actingAs($user)->get(route('admin.workspace.records.show', [
            'module' => 'maintenance-requests',
            'record' => $request->id,
        ]));

        $showResponse->assertOk();
        $showResponse->assertSeeInOrder([$expectedRoute, 'sidebar-link sidebar-link-active', 'Maintenance Requests'], false);

        $editResponse = $this->actingAs($user)->get(route('admin.workspace.records.edit', [
            'module' => 'maintenance-requests',
            'record' => $request->id,
        ]));

        $editResponse->assertOk();
        $editResponse->assertSeeInOrder([$expectedRoute, 'sidebar-link sidebar-link-active', 'Maintenance Requests'], false);
    }

    public function test_tenant_can_view_stock_movements_from_workspace_sidebar(): void
    {
        $company = Company::query()->create(['name' => 'Atlas Hotels', 'status' => Company::STATUS_ACTIVE]);
        $user = $this->createTenantUser($company, [
            'read-dashboard', 'read-inventory-item',
        ]);

        $item = InventoryItem::query()->create([
            'company_id' => $company->id,
            'sku' => 'INV-MOVE-1',
            'name' => 'Mini Bar Soda',
            'category' => 'beverage',
            'unit_of_measure' => 'case',
            'current_quantity' => 24,
            'reorder_level' => 6,
            'par_level' => 12,
            'unit_cost' => 9.5,
            'is_active' => true,
        ]);

        $movement = InventoryMovement::query()->create([
            'company_id' => $company->id,
            'inventory_item_id' => $item->id,
            'movement_type' => InventoryMovement::TYPE_RECEIPT,
            'quantity_change' => 10,
            'unit_cost' => 9.5,
            'moved_at' => now(),
            'notes' => 'Received into main store.',
        ]);

        $indexResponse = $this->actingAs($user)->get(route('admin.workspace.modules.show', [
            'module' => 'inventory-movements',
            'search' => 'Mini Bar',
            'movement_type' => InventoryMovement::TYPE_RECEIPT,
        ]));

        $indexResponse->assertOk();
        $indexResponse->assertSee('Stock Movements');
        $indexResponse->assertSee('Mini Bar Soda');
        $indexResponse->assertSee('Receipt');

        $showResponse = $this->actingAs($user)->get(route('admin.workspace.records.show', [
            'module' => 'inventory-movements',
            'record' => $movement->id,
        ]));

        $showResponse->assertOk();
        $showResponse->assertSee('Mini Bar Soda');
        $showResponse->assertSee('Movement details');
        $showResponse->assertSee('Received into main store.');
    }

    public function test_super_admin_navigation_remains_platform_scoped(): void
    {
        $superAdmin = User::factory()->create(['company_id' => null]);
        $superAdminRole = Role::withoutGlobalScopes()->create([
            'company_id' => null,
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ]);
        $superAdmin->assignRole($superAdminRole);

        $response = $this->actingAs($superAdmin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Admin Panel');
        $response->assertSee('Platform Control');
        $response->assertSee('Companies');
        $response->assertSee('Reports');
        $response->assertSee('Intelligence');
        $response->assertDontSee('Front Desk');
    }

    public function test_tenant_can_open_record_detail_pages_for_operational_modules(): void
    {
        $company = Company::query()->create(['name' => 'Atlas Hotels', 'status' => Company::STATUS_ACTIVE]);
        $user = $this->createTenantUser($company, [
            'read-dashboard', 'read-reservation', 'update-reservation',
        ]);

        $property = Property::query()->create([
            'company_id' => $company->id,
            'branch_code' => 'ATL-OPS',
            'name' => 'Atlas Operations',
            'property_type' => 'hotel',
            'timezone' => 'UTC',
            'currency_code' => 'USD',
            'status' => 'active',
        ]);

        $roomType = RoomType::query()->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'name' => 'Suite',
            'code' => 'STE',
            'base_rate' => 320,
            'capacity_adults' => 2,
            'capacity_children' => 2,
            'status' => 'active',
        ]);

        $room = Room::query()->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'room_number' => '901',
            'floor_label' => '9',
            'status' => Room::STATUS_AVAILABLE,
            'cleaning_status' => 'clean',
        ]);

        $guest = \App\Domain\Hotel\Models\GuestProfile::query()->create([
            'company_id' => $company->id,
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.test',
        ]);

        $reservation = Reservation::query()->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'room_id' => $room->id,
            'guest_profile_id' => $guest->id,
            'status' => Reservation::STATUS_CONFIRMED,
            'currency_code' => 'USD',
            'check_in_date' => now()->addDay()->toDateString(),
            'check_out_date' => now()->addDays(3)->toDateString(),
            'adult_count' => 2,
            'child_count' => 0,
            'night_count' => 2,
            'rate_amount' => 320,
            'tax_amount' => 32,
            'total_amount' => 672,
        ]);

        $response = $this->actingAs($user)->get(route('admin.workspace.records.show', [
            'module' => 'reservations',
            'record' => $reservation->id,
        ]));

        $response->assertOk();
        $response->assertSee($reservation->reservation_number);
        $response->assertSee('Pre-arrival registration');
        $response->assertSee('Check in guest');
    }

    public function test_tenant_can_run_workspace_record_actions(): void
    {
        $company = Company::query()->create(['name' => 'Atlas Hotels', 'status' => Company::STATUS_ACTIVE]);
        $user = $this->createTenantUser($company, [
            'read-dashboard', 'read-maintenance-request', 'update-maintenance-request',
            'read-bank-reconciliation', 'update-bank-reconciliation',
        ]);

        $property = Property::query()->create([
            'company_id' => $company->id,
            'branch_code' => 'ATL-ENG',
            'name' => 'Atlas Engineering',
            'property_type' => 'hotel',
            'timezone' => 'UTC',
            'currency_code' => 'USD',
            'status' => 'active',
        ]);

        $maintenanceRequest = MaintenanceRequest::query()->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'reported_by_user_id' => $user->id,
            'title' => 'Boiler pressure issue',
            'priority' => MaintenanceRequest::PRIORITY_HIGH,
            'status' => MaintenanceRequest::STATUS_OPEN,
            'reported_at' => now(),
        ]);

        $bankAccount = BankAccount::query()->create([
            'company_id' => $company->id,
            'name' => 'Operations Checking',
            'currency_code' => 'USD',
            'current_balance' => 1000,
            'is_active' => true,
        ]);

        $reconciliation = BankReconciliation::query()->create([
            'company_id' => $company->id,
            'bank_account_id' => $bankAccount->id,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->toDateString(),
            'statement_ending_balance' => 1000,
            'book_ending_balance' => 1000,
            'cleared_balance' => 1000,
            'status' => BankReconciliation::STATUS_OPEN,
        ]);

        $maintenanceResponse = $this->actingAs($user)->post(route('admin.workspace.records.actions.store', [
            'module' => 'maintenance-requests',
            'record' => $maintenanceRequest->id,
            'action' => 'update',
        ]), [
            'status' => MaintenanceRequest::STATUS_IN_PROGRESS,
            'priority' => MaintenanceRequest::PRIORITY_URGENT,
            'technician_notes' => 'Technician dispatched.',
        ]);

        $maintenanceResponse->assertRedirect(route('admin.workspace.records.show', [
            'module' => 'maintenance-requests',
            'record' => $maintenanceRequest->id,
        ]));

        $this->assertDatabaseHas('hotel_maintenance_requests', [
            'id' => $maintenanceRequest->id,
            'status' => MaintenanceRequest::STATUS_IN_PROGRESS,
            'priority' => MaintenanceRequest::PRIORITY_URGENT,
            'technician_notes' => 'Technician dispatched.',
        ]);

        $reconciliationResponse = $this->actingAs($user)->post(route('admin.workspace.records.actions.store', [
            'module' => 'bank-reconciliations',
            'record' => $reconciliation->id,
            'action' => 'complete',
        ]));

        $reconciliationResponse->assertRedirect(route('admin.workspace.records.show', [
            'module' => 'bank-reconciliations',
            'record' => $reconciliation->id,
        ]));

        $this->assertDatabaseHas('accounting_bank_reconciliations', [
            'id' => $reconciliation->id,
            'status' => BankReconciliation::STATUS_COMPLETED,
        ]);
    }

    public function test_tenant_can_use_expanded_workspace_create_flows(): void
    {
        $company = Company::query()->create(['name' => 'Atlas Hotels', 'status' => Company::STATUS_ACTIVE]);
        $user = $this->createTenantUser($company, [
            'read-dashboard', 'read-bank-account', 'create-bank-account',
            'read-supplier', 'create-supplier',
            'read-inventory-item', 'create-inventory-item',
        ]);

        $bankAccountCreate = $this->actingAs($user)->get(route('admin.workspace.modules.create', ['module' => 'bank-accounts']));
        $bankAccountCreate->assertOk();
        $bankAccountCreate->assertSee('Create Bank Accounts');

        $supplierResponse = $this->actingAs($user)->post(route('admin.workspace.modules.store', ['module' => 'suppliers']), [
            'name' => 'Northwind Foods',
            'email' => 'procurement@example.test',
            'status' => 'active',
        ]);

        $supplierResponse->assertRedirect(route('admin.workspace.modules.show', ['module' => 'suppliers']));
        $supplier = Supplier::query()->where('name', 'Northwind Foods')->firstOrFail();

        $bankAccountResponse = $this->actingAs($user)->post(route('admin.workspace.modules.store', ['module' => 'bank-accounts']), [
            'name' => 'Main Operating Account',
            'bank_name' => 'Atlas Bank',
            'currency_code' => 'USD',
            'current_balance' => 15000,
            'is_active' => true,
            'opened_at' => now()->toDateString(),
        ]);

        $bankAccountResponse->assertRedirect(route('admin.workspace.modules.show', ['module' => 'bank-accounts']));
        $this->assertDatabaseHas('accounting_bank_accounts', [
            'company_id' => $company->id,
            'name' => 'Main Operating Account',
        ]);

        $inventoryResponse = $this->actingAs($user)->post(route('admin.workspace.modules.store', ['module' => 'inventory-items']), [
            'preferred_supplier_id' => $supplier->id,
            'sku' => 'INV-200',
            'name' => 'Espresso Beans',
            'category' => 'beverage',
            'unit_of_measure' => 'kg',
            'current_quantity' => 12,
            'reorder_level' => 5,
            'par_level' => 15,
            'unit_cost' => 8.5,
            'is_active' => true,
        ]);

        $inventoryResponse->assertRedirect(route('admin.workspace.modules.show', ['module' => 'inventory-items']));
        $this->assertDatabaseHas('inventory_items', [
            'company_id' => $company->id,
            'sku' => 'INV-200',
            'name' => 'Espresso Beans',
        ]);
    }

    public function test_tenant_can_edit_master_data_records_from_record_pages(): void
    {
        $company = Company::query()->create(['name' => 'Atlas Hotels', 'status' => Company::STATUS_ACTIVE]);
        $user = $this->createTenantUser($company, [
            'read-dashboard', 'read-property', 'create-property',
        ]);

        $property = Property::query()->create([
            'company_id' => $company->id,
            'branch_code' => 'ATL-OLD',
            'name' => 'Atlas Legacy',
            'property_type' => 'hotel',
            'timezone' => 'UTC',
            'currency_code' => 'USD',
            'status' => 'active',
        ]);

        $showResponse = $this->actingAs($user)->get(route('admin.workspace.records.show', [
            'module' => 'properties',
            'record' => $property->id,
        ]));

        $showResponse->assertOk();
        $showResponse->assertSee('Edit');
        $showResponse->assertSee('Atlas Legacy');

        $editResponse = $this->actingAs($user)->get(route('admin.workspace.records.edit', [
            'module' => 'properties',
            'record' => $property->id,
        ]));

        $editResponse->assertOk();
        $editResponse->assertSee('Edit Properties');
        $editResponse->assertSee('Atlas Legacy');

        $updateResponse = $this->actingAs($user)->put(route('admin.workspace.records.update', [
            'module' => 'properties',
            'record' => $property->id,
        ]), [
            'branch_code' => 'ATL-NEW',
            'name' => 'Atlas Modernized',
            'property_type' => 'hotel',
            'timezone' => 'Africa/Nairobi',
            'currency_code' => 'KES',
            'check_in_time' => '15:00',
            'check_out_time' => '11:00',
            'status' => 'active',
        ]);

        $updateResponse->assertRedirect(route('admin.workspace.records.show', [
            'module' => 'properties',
            'record' => $property->id,
        ]));

        $this->assertDatabaseHas('hotel_properties', [
            'id' => $property->id,
            'branch_code' => 'ATL-NEW',
            'name' => 'Atlas Modernized',
            'timezone' => 'Africa/Nairobi',
            'currency_code' => 'KES',
        ]);
    }

    public function test_tenant_can_edit_operational_records_from_record_pages(): void
    {
        $company = Company::query()->create(['name' => 'Atlas Hotels', 'status' => Company::STATUS_ACTIVE]);
        $user = $this->createTenantUser($company, [
            'read-dashboard',
            'read-maintenance-request', 'update-maintenance-request',
            'read-housekeeping-task', 'update-housekeeping-task',
            'read-preventive-maintenance-schedule', 'update-preventive-maintenance-schedule',
        ]);

        $property = Property::query()->create([
            'company_id' => $company->id,
            'branch_code' => 'ATL-OPS',
            'name' => 'Atlas Ops',
            'property_type' => 'hotel',
            'timezone' => 'UTC',
            'currency_code' => 'USD',
            'status' => 'active',
        ]);

        $roomType = RoomType::query()->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'name' => 'Deluxe',
            'code' => 'DLX',
            'base_rate' => 100,
            'capacity_adults' => 2,
            'capacity_children' => 1,
            'status' => 'active',
        ]);

        $room = Room::query()->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'room_number' => '305',
            'floor_label' => '3',
            'status' => Room::STATUS_AVAILABLE,
            'cleaning_status' => 'dirty',
        ]);

        $guest = \App\Domain\Hotel\Models\GuestProfile::query()->create([
            'company_id' => $company->id,
            'first_name' => 'Mina',
            'last_name' => 'Hart',
        ]);

        $reservation = Reservation::query()->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'room_id' => $room->id,
            'guest_profile_id' => $guest->id,
            'status' => Reservation::STATUS_CHECKED_IN,
            'currency_code' => 'USD',
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDay()->toDateString(),
            'adult_count' => 2,
            'night_count' => 1,
            'rate_amount' => 100,
            'tax_amount' => 10,
            'total_amount' => 110,
        ]);

        $maintenanceRequest = MaintenanceRequest::query()->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'room_id' => $room->id,
            'reported_by_user_id' => $user->id,
            'title' => 'Fan issue',
            'priority' => MaintenanceRequest::PRIORITY_MEDIUM,
            'status' => MaintenanceRequest::STATUS_OPEN,
            'reported_at' => now(),
        ]);

        $housekeepingTask = HousekeepingTask::query()->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'room_id' => $room->id,
            'reservation_id' => $reservation->id,
            'task_type' => HousekeepingTask::TYPE_CHECKOUT_CLEANING,
            'status' => HousekeepingTask::STATUS_PENDING,
            'priority' => 'high',
            'scheduled_for' => now(),
        ]);

        $schedule = PreventiveMaintenanceSchedule::query()->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'room_id' => $room->id,
            'title' => 'AC monthly inspection',
            'maintenance_category' => 'hvac',
            'priority' => MaintenanceRequest::PRIORITY_MEDIUM,
            'frequency_days' => 30,
            'next_due_at' => now()->addDays(30),
            'is_active' => true,
        ]);

        $this->actingAs($user)->get(route('admin.workspace.records.show', ['module' => 'maintenance-requests', 'record' => $maintenanceRequest->id]))
            ->assertOk()
            ->assertSee('Edit');

        $this->actingAs($user)->get(route('admin.workspace.records.edit', ['module' => 'maintenance-requests', 'record' => $maintenanceRequest->id]))
            ->assertOk()
            ->assertSee('Edit Maintenance Requests');

        $this->actingAs($user)->put(route('admin.workspace.records.update', ['module' => 'maintenance-requests', 'record' => $maintenanceRequest->id]), [
            'property_id' => $property->id,
            'room_id' => $room->id,
            'assigned_to_user_id' => $user->id,
            'title' => 'Fan motor replacement',
            'description' => 'Replace worn fan motor.',
            'maintenance_category' => 'engineering',
            'priority' => MaintenanceRequest::PRIORITY_HIGH,
            'status' => MaintenanceRequest::STATUS_IN_PROGRESS,
            'scheduled_for' => now()->addHour()->format('Y-m-d H:i:s'),
            'technician_notes' => 'Parts requested.',
        ])->assertRedirect(route('admin.workspace.records.show', ['module' => 'maintenance-requests', 'record' => $maintenanceRequest->id]));

        $this->actingAs($user)->put(route('admin.workspace.records.update', ['module' => 'housekeeping-tasks', 'record' => $housekeepingTask->id]), [
            'property_id' => $property->id,
            'room_id' => $room->id,
            'reservation_id' => $reservation->id,
            'assigned_to_user_id' => $user->id,
            'task_type' => HousekeepingTask::TYPE_TURNDOWN,
            'status' => HousekeepingTask::STATUS_IN_PROGRESS,
            'priority' => 'standard',
            'linen_status' => HousekeepingTask::LINEN_STATUS_PENDING,
            'minibar_status' => HousekeepingTask::MINIBAR_STATUS_PENDING,
            'inspection_status' => HousekeepingTask::INSPECTION_STATUS_PASSED,
            'inspection_notes' => 'Looks ready.',
            'scheduled_for' => now()->addHour()->format('Y-m-d H:i:s'),
            'notes' => 'Guest requested turndown.',
        ])->assertRedirect(route('admin.workspace.records.show', ['module' => 'housekeeping-tasks', 'record' => $housekeepingTask->id]));

        $this->actingAs($user)->put(route('admin.workspace.records.update', ['module' => 'preventive-maintenance-schedules', 'record' => $schedule->id]), [
            'property_id' => $property->id,
            'room_id' => $room->id,
            'assigned_to_user_id' => $user->id,
            'title' => 'AC quarterly inspection',
            'description' => 'Inspect AC vents and filters.',
            'maintenance_category' => 'hvac',
            'priority' => MaintenanceRequest::PRIORITY_HIGH,
            'frequency_days' => 90,
            'next_due_at' => now()->addDays(90)->format('Y-m-d H:i:s'),
            'is_active' => true,
            'notes' => 'Escalated to quarterly cadence.',
        ])->assertRedirect(route('admin.workspace.records.show', ['module' => 'preventive-maintenance-schedules', 'record' => $schedule->id]));

        $this->assertDatabaseHas('hotel_maintenance_requests', [
            'id' => $maintenanceRequest->id,
            'title' => 'Fan motor replacement',
            'status' => MaintenanceRequest::STATUS_IN_PROGRESS,
            'assigned_to_user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('hotel_housekeeping_tasks', [
            'id' => $housekeepingTask->id,
            'task_type' => HousekeepingTask::TYPE_TURNDOWN,
            'status' => HousekeepingTask::STATUS_IN_PROGRESS,
            'assigned_to_user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('hotel_preventive_maintenance_schedules', [
            'id' => $schedule->id,
            'title' => 'AC quarterly inspection',
            'frequency_days' => 90,
            'assigned_to_user_id' => $user->id,
        ]);
    }

    public function test_tenant_module_tables_support_search_filters_and_bulk_actions(): void
    {
        $company = Company::query()->create(['name' => 'Atlas Hotels', 'status' => Company::STATUS_ACTIVE]);
        $user = $this->createTenantUser($company, [
            'read-dashboard', 'read-maintenance-request', 'update-maintenance-request',
        ]);

        $property = Property::query()->create([
            'company_id' => $company->id,
            'branch_code' => 'ATL-ENG',
            'name' => 'Atlas Engineering',
            'property_type' => 'hotel',
            'timezone' => 'UTC',
            'currency_code' => 'USD',
            'status' => 'active',
        ]);

        $match = MaintenanceRequest::query()->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'reported_by_user_id' => $user->id,
            'title' => 'Boiler alarm',
            'priority' => MaintenanceRequest::PRIORITY_HIGH,
            'status' => MaintenanceRequest::STATUS_OPEN,
            'reported_at' => now(),
        ]);

        $other = MaintenanceRequest::query()->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'reported_by_user_id' => $user->id,
            'title' => 'Lobby bulb replacement',
            'priority' => MaintenanceRequest::PRIORITY_LOW,
            'status' => MaintenanceRequest::STATUS_CANCELLED,
            'reported_at' => now(),
        ]);

        $filterResponse = $this->actingAs($user)->get(route('admin.workspace.modules.show', [
            'module' => 'maintenance-requests',
            'search' => 'Boiler',
            'status' => MaintenanceRequest::STATUS_OPEN,
            'priority' => MaintenanceRequest::PRIORITY_HIGH,
            'property_id' => $property->id,
        ]));

        $filterResponse->assertOk();
        $filterResponse->assertSee('Boiler alarm');
        $filterResponse->assertDontSee('Lobby bulb replacement');
        $filterResponse->assertSee('Run bulk action');

        $bulkResponse = $this->actingAs($user)->post(route('admin.workspace.modules.bulk-actions.store', [
            'module' => 'maintenance-requests',
        ]), [
            'record_ids' => [$match->id, $other->id],
            'bulk_action' => 'mark_completed',
        ]);

        $bulkResponse->assertRedirect();

        $this->assertDatabaseHas('hotel_maintenance_requests', [
            'id' => $match->id,
            'status' => MaintenanceRequest::STATUS_COMPLETED,
        ]);

        $this->assertDatabaseHas('hotel_maintenance_requests', [
            'id' => $other->id,
            'status' => MaintenanceRequest::STATUS_COMPLETED,
        ]);
    }

    public function test_finance_workflow_handles_partial_payment_and_refund_edges(): void
    {
        $company = Company::query()->create(['name' => 'Atlas Hotels', 'status' => Company::STATUS_ACTIVE]);
        $user = $this->createTenantUser($company, [
            'read-dashboard', 'read-invoice', 'create-payment', 'create-refund',
        ]);

        $guest = \App\Domain\Hotel\Models\GuestProfile::query()->create([
            'company_id' => $company->id,
            'first_name' => 'Leah',
            'last_name' => 'Stone',
            'email' => 'leah@example.test',
        ]);

        $invoice = Invoice::query()->create([
            'company_id' => $company->id,
            'guest_profile_id' => $guest->id,
            'status' => Invoice::STATUS_ISSUED,
            'currency_code' => 'USD',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(3)->toDateString(),
            'subtotal_amount' => 180,
            'tax_amount' => 20,
            'total_amount' => 200,
            'balance_amount' => 200,
        ]);

        $paymentResponse = $this->actingAs($user)->post(route('admin.workspace.records.actions.store', [
            'module' => 'invoices',
            'record' => $invoice->id,
            'action' => 'post-payment',
        ]), [
            'payment_method' => 'cash',
            'paid_at' => now()->format('Y-m-d H:i:s'),
            'amount' => 140,
            'reference' => 'PMT-001',
        ]);

        $paymentResponse->assertRedirect(route('admin.workspace.records.show', ['module' => 'invoices', 'record' => $invoice->id]));

        $payment = Payment::query()->where('invoice_id', $invoice->id)->latest('id')->firstOrFail();

        $refundResponse = $this->actingAs($user)->post(route('admin.workspace.records.actions.store', [
            'module' => 'invoices',
            'record' => $invoice->id,
            'action' => 'post-refund',
        ]), [
            'payment_id' => $payment->id,
            'refunded_at' => now()->format('Y-m-d H:i:s'),
            'amount' => 20,
            'reason' => 'Service recovery credit',
        ]);

        $refundResponse->assertRedirect(route('admin.workspace.records.show', ['module' => 'invoices', 'record' => $invoice->id]));

        $invoice->refresh();

        $this->assertSame(80.0, (float) $invoice->balance_amount);
        $this->assertSame(Invoice::STATUS_REFUNDED, $invoice->status);
        $this->assertDatabaseHas('accounting_payments', [
            'invoice_id' => $invoice->id,
            'amount' => 140,
        ]);
        $this->assertDatabaseHas('accounting_refunds', [
            'invoice_id' => $invoice->id,
            'amount' => 20,
            'reason' => 'Service recovery credit',
        ]);
    }

    public function test_procurement_workflow_handles_approval_and_receiving_edges(): void
    {
        $company = Company::query()->create(['name' => 'Atlas Hotels', 'status' => Company::STATUS_ACTIVE]);
        $user = $this->createTenantUser($company, [
            'read-dashboard', 'read-purchase-order', 'update-purchase-order',
        ]);

        $property = Property::query()->create([
            'company_id' => $company->id,
            'branch_code' => 'ATL-SUP',
            'name' => 'Atlas Supply',
            'property_type' => 'hotel',
            'timezone' => 'UTC',
            'currency_code' => 'USD',
            'status' => 'active',
        ]);

        $supplier = Supplier::query()->create([
            'company_id' => $company->id,
            'name' => 'Supply House',
            'status' => 'active',
        ]);

        $item = InventoryItem::query()->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'preferred_supplier_id' => $supplier->id,
            'sku' => 'INV-PO-1',
            'name' => 'Laundry Detergent',
            'category' => 'housekeeping',
            'unit_of_measure' => 'case',
            'current_quantity' => 2,
            'reorder_level' => 5,
            'par_level' => 10,
            'unit_cost' => 12,
            'is_active' => true,
        ]);

        $order = PurchaseOrder::query()->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'supplier_id' => $supplier->id,
            'status' => PurchaseOrder::STATUS_DRAFT,
            'match_status' => PurchaseOrder::MATCH_STATUS_UNMATCHED,
            'currency_code' => 'USD',
            'order_date' => now()->toDateString(),
            'expected_delivery_date' => now()->addDay()->toDateString(),
            'subtotal_amount' => 60,
            'tax_amount' => 6,
            'total_amount' => 66,
        ]);

        $line = PurchaseOrderLine::query()->create([
            'company_id' => $company->id,
            'purchase_order_id' => $order->id,
            'inventory_item_id' => $item->id,
            'description' => 'Laundry Detergent',
            'ordered_quantity' => 5,
            'received_quantity' => 0,
            'billed_quantity' => 0,
            'unit_cost' => 12,
            'tax_amount' => 6,
            'total_amount' => 66,
        ]);

        PurchaseOrderApproval::query()->create([
            'company_id' => $company->id,
            'purchase_order_id' => $order->id,
            'sequence_number' => 1,
            'approver_user_id' => $user->id,
            'status' => PurchaseOrderApproval::STATUS_PENDING,
        ]);

        $approveResponse = $this->actingAs($user)->post(route('admin.workspace.records.actions.store', [
            'module' => 'purchase-orders',
            'record' => $order->id,
            'action' => 'approve',
        ]), [
            'notes' => 'Approved for urgent replenishment.',
        ]);

        $approveResponse->assertRedirect(route('admin.workspace.records.show', ['module' => 'purchase-orders', 'record' => $order->id]));

        $receiveResponse = $this->actingAs($user)->post(route('admin.workspace.records.actions.store', [
            'module' => 'purchase-orders',
            'record' => $order->id,
            'action' => 'receive',
        ]), [
            'received_at' => now()->format('Y-m-d H:i:s'),
            'notes' => 'Delivered in full.',
            'lines' => [[
                'purchase_order_line_id' => $line->id,
                'received_quantity' => 5,
                'unit_cost' => 12,
            ]],
        ]);

        $receiveResponse->assertRedirect(route('admin.workspace.records.show', ['module' => 'purchase-orders', 'record' => $order->id]));

        $order->refresh();
        $item->refresh();

        $this->assertSame(PurchaseOrder::STATUS_RECEIVED, $order->status);
        $this->assertSame(7.0, (float) $item->current_quantity);
        $this->assertDatabaseHas('procurement_purchase_order_approvals', [
            'purchase_order_id' => $order->id,
            'status' => PurchaseOrderApproval::STATUS_APPROVED,
        ]);
    }

    public function test_pos_workflow_handles_room_charge_posting_edge(): void
    {
        $company = Company::query()->create(['name' => 'Atlas Hotels', 'status' => Company::STATUS_ACTIVE]);
        $user = $this->createTenantUser($company, [
            'read-dashboard', 'read-pos-order', 'update-pos-order',
        ]);

        $property = Property::query()->create([
            'company_id' => $company->id,
            'branch_code' => 'ATL-POS',
            'name' => 'Atlas Dining',
            'property_type' => 'hotel',
            'timezone' => 'UTC',
            'currency_code' => 'USD',
            'status' => 'active',
        ]);

        $roomType = RoomType::query()->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'name' => 'Suite',
            'code' => 'STE',
            'base_rate' => 200,
            'capacity_adults' => 2,
            'capacity_children' => 1,
            'status' => 'active',
        ]);

        $room = Room::query()->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'room_number' => '812',
            'floor_label' => '8',
            'status' => Room::STATUS_OCCUPIED,
            'cleaning_status' => 'clean',
        ]);

        $guest = \App\Domain\Hotel\Models\GuestProfile::query()->create([
            'company_id' => $company->id,
            'first_name' => 'Nia',
            'last_name' => 'Cole',
        ]);

        $reservation = Reservation::query()->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'room_id' => $room->id,
            'guest_profile_id' => $guest->id,
            'status' => Reservation::STATUS_CHECKED_IN,
            'currency_code' => 'USD',
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDay()->toDateString(),
            'adult_count' => 1,
            'night_count' => 1,
            'rate_amount' => 200,
            'tax_amount' => 20,
            'total_amount' => 220,
        ]);

        $shift = PosCashierShift::query()->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'user_id' => $user->id,
            'status' => PosCashierShift::STATUS_OPEN,
            'opening_cash_amount' => 100,
        ]);

        $order = PosOrder::query()->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'cashier_shift_id' => $shift->id,
            'reservation_id' => $reservation->id,
            'status' => PosOrder::STATUS_PAID,
            'payment_method' => 'room_charge',
            'service_location' => 'room_service',
            'charge_to_room' => true,
            'paid_at' => now(),
            'subtotal_amount' => 30,
            'tax_amount' => 3,
            'total_amount' => 33,
        ]);

        $order->lines()->create([
            'company_id' => $company->id,
            'item_name' => 'Club Sandwich',
            'category' => 'food',
            'quantity' => 1,
            'unit_price' => 30,
            'tax_amount' => 3,
            'total_amount' => 33,
        ]);

        $response = $this->actingAs($user)->post(route('admin.workspace.records.actions.store', [
            'module' => 'pos-orders',
            'record' => $order->id,
            'action' => 'post-to-folio',
        ]));

        $response->assertRedirect(route('admin.workspace.records.show', ['module' => 'pos-orders', 'record' => $order->id]));

        $order->refresh();

        $this->assertNotNull($order->folio_id);
        $this->assertNotNull($order->posted_to_folio_at);
        $this->assertDatabaseHas('hotel_folios', [
            'id' => $order->folio_id,
            'reservation_id' => $reservation->id,
        ]);
        $this->assertDatabaseHas('hotel_folio_lines', [
            'folio_id' => $order->folio_id,
            'description' => 'Club Sandwich',
            'line_type' => 'pos_charge',
        ]);
    }

    protected function createTenantUser(Company $company, array $permissionNames): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);

        $role = Role::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Tenant Admin '.$company->id.'-'.str()->random(5),
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