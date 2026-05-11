<?php

use App\Http\Controllers\Api\V1\FolioController;
use App\Http\Controllers\Api\V1\FinanceReportingController;
use App\Http\Controllers\Api\V1\GuestProfileController;
use App\Http\Controllers\Api\V1\HousekeepingTaskController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\InventoryItemController;
use App\Http\Controllers\Api\V1\BankAccountController;
use App\Http\Controllers\Api\V1\BankReconciliationController;
use App\Http\Controllers\Api\V1\MaintenanceRequestController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PosCashierShiftController;
use App\Http\Controllers\Api\V1\PosOrderController;
use App\Http\Controllers\Api\V1\PreventiveMaintenanceScheduleController;
use App\Http\Controllers\Api\V1\PropertyController;
use App\Http\Controllers\Api\V1\PurchaseOrderController;
use App\Http\Controllers\Api\V1\RefundController;
use App\Http\Controllers\Api\V1\ReservationController;
use App\Http\Controllers\Api\V1\ReservationOperationsController;
use App\Http\Controllers\Api\V1\RoomController;
use App\Http\Controllers\Api\V1\SupplierBillController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\SupplierPaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'set-company-context', 'tenant-company-active'])
    ->prefix('v1')
    ->as('api.v1.')
    ->group(function () {
        Route::get('properties', [PropertyController::class, 'index'])->middleware('permission:read-property')->name('properties.index');
        Route::post('properties', [PropertyController::class, 'store'])->middleware('permission:create-property')->name('properties.store');
        Route::get('properties/{property}', [PropertyController::class, 'show'])->middleware('permission:show-property')->name('properties.show');
        Route::match(['put', 'patch'], 'properties/{property}', [PropertyController::class, 'update'])->middleware('permission:update-property')->name('properties.update');
        Route::delete('properties/{property}', [PropertyController::class, 'destroy'])->middleware('permission:delete-property')->name('properties.destroy');

        Route::get('rooms', [RoomController::class, 'index'])->middleware('permission:read-room')->name('rooms.index');
        Route::post('rooms', [RoomController::class, 'store'])->middleware('permission:create-room')->name('rooms.store');
        Route::get('rooms/{room}', [RoomController::class, 'show'])->middleware('permission:show-room')->name('rooms.show');
        Route::match(['put', 'patch'], 'rooms/{room}', [RoomController::class, 'update'])->middleware('permission:update-room')->name('rooms.update');
        Route::delete('rooms/{room}', [RoomController::class, 'destroy'])->middleware('permission:delete-room')->name('rooms.destroy');

        Route::get('guests', [GuestProfileController::class, 'index'])->middleware('permission:read-guest')->name('guests.index');
        Route::post('guests', [GuestProfileController::class, 'store'])->middleware('permission:create-guest')->name('guests.store');
        Route::get('guests/{guest}', [GuestProfileController::class, 'show'])->middleware('permission:show-guest')->name('guests.show');
        Route::match(['put', 'patch'], 'guests/{guest}', [GuestProfileController::class, 'update'])->middleware('permission:update-guest')->name('guests.update');
        Route::delete('guests/{guest}', [GuestProfileController::class, 'destroy'])->middleware('permission:delete-guest')->name('guests.destroy');

        Route::get('reservations', [ReservationController::class, 'index'])->middleware('permission:read-reservation')->name('reservations.index');
        Route::post('reservations', [ReservationController::class, 'store'])->middleware('permission:create-reservation')->name('reservations.store');
        Route::get('reservations/{reservation}', [ReservationController::class, 'show'])->middleware('permission:show-reservation')->name('reservations.show');
        Route::match(['put', 'patch'], 'reservations/{reservation}', [ReservationController::class, 'update'])->middleware('permission:update-reservation')->name('reservations.update');
        Route::delete('reservations/{reservation}', [ReservationController::class, 'destroy'])->middleware('permission:delete-reservation')->name('reservations.destroy');
        Route::post('reservations/{reservation}/pre-arrival-registration', [ReservationOperationsController::class, 'preArrivalRegistration'])->middleware('permission:update-reservation')->name('reservations.pre-arrival-registration');
        Route::post('reservations/{reservation}/check-in', [ReservationOperationsController::class, 'checkIn'])->middleware('permission:update-reservation')->name('reservations.check-in');
        Route::post('reservations/{reservation}/check-out', [ReservationOperationsController::class, 'checkOut'])->middleware('permission:update-reservation')->name('reservations.check-out');
        Route::post('reservations/{reservation}/move-room', [ReservationOperationsController::class, 'moveRoom'])->middleware('permission:update-reservation')->name('reservations.move-room');

        Route::get('housekeeping-tasks', [HousekeepingTaskController::class, 'index'])->middleware('permission:read-housekeeping-task')->name('housekeeping-tasks.index');
        Route::post('housekeeping-tasks', [HousekeepingTaskController::class, 'store'])->middleware('permission:create-housekeeping-task')->name('housekeeping-tasks.store');
        Route::get('housekeeping-tasks/{housekeepingTask}', [HousekeepingTaskController::class, 'show'])->middleware('permission:show-housekeeping-task')->name('housekeeping-tasks.show');
        Route::match(['put', 'patch'], 'housekeeping-tasks/{housekeepingTask}', [HousekeepingTaskController::class, 'update'])->middleware('permission:update-housekeeping-task')->name('housekeeping-tasks.update');
        Route::delete('housekeeping-tasks/{housekeepingTask}', [HousekeepingTaskController::class, 'destroy'])->middleware('permission:delete-housekeeping-task')->name('housekeeping-tasks.destroy');

        Route::get('maintenance-requests', [MaintenanceRequestController::class, 'index'])->middleware('permission:read-maintenance-request')->name('maintenance-requests.index');
        Route::post('maintenance-requests', [MaintenanceRequestController::class, 'store'])->middleware('permission:create-maintenance-request')->name('maintenance-requests.store');
        Route::get('maintenance-requests/{maintenanceRequest}', [MaintenanceRequestController::class, 'show'])->middleware('permission:show-maintenance-request')->name('maintenance-requests.show');
        Route::match(['put', 'patch'], 'maintenance-requests/{maintenanceRequest}', [MaintenanceRequestController::class, 'update'])->middleware('permission:update-maintenance-request')->name('maintenance-requests.update');
        Route::delete('maintenance-requests/{maintenanceRequest}', [MaintenanceRequestController::class, 'destroy'])->middleware('permission:delete-maintenance-request')->name('maintenance-requests.destroy');

        Route::get('preventive-maintenance-schedules', [PreventiveMaintenanceScheduleController::class, 'index'])->middleware('permission:read-preventive-maintenance-schedule')->name('preventive-maintenance-schedules.index');
        Route::post('preventive-maintenance-schedules', [PreventiveMaintenanceScheduleController::class, 'store'])->middleware('permission:create-preventive-maintenance-schedule')->name('preventive-maintenance-schedules.store');
        Route::get('preventive-maintenance-schedules/{preventiveMaintenanceSchedule}', [PreventiveMaintenanceScheduleController::class, 'show'])->middleware('permission:show-preventive-maintenance-schedule')->name('preventive-maintenance-schedules.show');
        Route::match(['put', 'patch'], 'preventive-maintenance-schedules/{preventiveMaintenanceSchedule}', [PreventiveMaintenanceScheduleController::class, 'update'])->middleware('permission:update-preventive-maintenance-schedule')->name('preventive-maintenance-schedules.update');
        Route::delete('preventive-maintenance-schedules/{preventiveMaintenanceSchedule}', [PreventiveMaintenanceScheduleController::class, 'destroy'])->middleware('permission:delete-preventive-maintenance-schedule')->name('preventive-maintenance-schedules.destroy');
        Route::post('preventive-maintenance-schedules/{preventiveMaintenanceSchedule}/generate', [PreventiveMaintenanceScheduleController::class, 'generate'])->middleware('permission:update-preventive-maintenance-schedule')->name('preventive-maintenance-schedules.generate');

        Route::get('folios', [FolioController::class, 'index'])->middleware('permission:read-folio')->name('folios.index');
        Route::post('folios', [FolioController::class, 'store'])->middleware('permission:create-folio')->name('folios.store');
        Route::get('folios/{folio}', [FolioController::class, 'show'])->middleware('permission:show-folio')->name('folios.show');
        Route::post('folios/{folio}/charges', [FolioController::class, 'addCharge'])->middleware('permission:update-folio')->name('folios.charges.store');

        Route::get('invoices', [InvoiceController::class, 'index'])->middleware('permission:read-invoice')->name('invoices.index');
        Route::post('invoices', [InvoiceController::class, 'store'])->middleware('permission:create-invoice')->name('invoices.store');
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->middleware('permission:show-invoice')->name('invoices.show');
        Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store'])->middleware('permission:create-payment')->name('invoices.payments.store');
        Route::post('invoices/{invoice}/refunds', [RefundController::class, 'store'])->middleware('permission:create-refund')->name('invoices.refunds.store');
        Route::get('finance/ar-aging', [FinanceReportingController::class, 'arAging'])->middleware('permission:read-report')->name('finance.ar-aging');
        Route::get('finance/ap-aging', [FinanceReportingController::class, 'apAging'])->middleware('permission:read-report')->name('finance.ap-aging');
        Route::get('bank-accounts', [BankAccountController::class, 'index'])->middleware('permission:read-bank-account')->name('bank-accounts.index');
        Route::post('bank-accounts', [BankAccountController::class, 'store'])->middleware('permission:create-bank-account')->name('bank-accounts.store');
        Route::get('bank-accounts/{bankAccount}', [BankAccountController::class, 'show'])->middleware('permission:show-bank-account')->name('bank-accounts.show');
        Route::match(['put', 'patch'], 'bank-accounts/{bankAccount}', [BankAccountController::class, 'update'])->middleware('permission:update-bank-account')->name('bank-accounts.update');
        Route::delete('bank-accounts/{bankAccount}', [BankAccountController::class, 'destroy'])->middleware('permission:delete-bank-account')->name('bank-accounts.destroy');
        Route::get('bank-reconciliations', [BankReconciliationController::class, 'index'])->middleware('permission:read-bank-reconciliation')->name('bank-reconciliations.index');
        Route::post('bank-reconciliations', [BankReconciliationController::class, 'store'])->middleware('permission:create-bank-reconciliation')->name('bank-reconciliations.store');
        Route::get('bank-reconciliations/{bankReconciliation}', [BankReconciliationController::class, 'show'])->middleware('permission:show-bank-reconciliation')->name('bank-reconciliations.show');
        Route::match(['put', 'patch'], 'bank-reconciliations/{bankReconciliation}', [BankReconciliationController::class, 'update'])->middleware('permission:update-bank-reconciliation')->name('bank-reconciliations.update');
        Route::delete('bank-reconciliations/{bankReconciliation}', [BankReconciliationController::class, 'destroy'])->middleware('permission:delete-bank-reconciliation')->name('bank-reconciliations.destroy');

        Route::get('inventory-items', [InventoryItemController::class, 'index'])->middleware('permission:read-inventory-item')->name('inventory-items.index');
        Route::post('inventory-items', [InventoryItemController::class, 'store'])->middleware('permission:create-inventory-item')->name('inventory-items.store');
        Route::get('inventory-items/{inventoryItem}', [InventoryItemController::class, 'show'])->middleware('permission:show-inventory-item')->name('inventory-items.show');
        Route::match(['put', 'patch'], 'inventory-items/{inventoryItem}', [InventoryItemController::class, 'update'])->middleware('permission:update-inventory-item')->name('inventory-items.update');
        Route::delete('inventory-items/{inventoryItem}', [InventoryItemController::class, 'destroy'])->middleware('permission:delete-inventory-item')->name('inventory-items.destroy');

        Route::get('purchase-orders', [PurchaseOrderController::class, 'index'])->middleware('permission:read-purchase-order')->name('purchase-orders.index');
        Route::post('purchase-orders', [PurchaseOrderController::class, 'store'])->middleware('permission:create-purchase-order')->name('purchase-orders.store');
        Route::get('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->middleware('permission:show-purchase-order')->name('purchase-orders.show');
        Route::post('purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->middleware('permission:update-purchase-order')->name('purchase-orders.approve');
        Route::post('purchase-orders/{purchaseOrder}/reject', [PurchaseOrderController::class, 'reject'])->middleware('permission:update-purchase-order')->name('purchase-orders.reject');
        Route::post('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->middleware('permission:update-purchase-order')->name('purchase-orders.receive');
        Route::delete('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])->middleware('permission:delete-purchase-order')->name('purchase-orders.destroy');

        Route::get('pos-cashier-shifts', [PosCashierShiftController::class, 'index'])->middleware('permission:read-pos-cashier-shift')->name('pos-cashier-shifts.index');
        Route::post('pos-cashier-shifts', [PosCashierShiftController::class, 'store'])->middleware('permission:create-pos-cashier-shift')->name('pos-cashier-shifts.store');
        Route::get('pos-cashier-shifts/{posCashierShift}', [PosCashierShiftController::class, 'show'])->middleware('permission:show-pos-cashier-shift')->name('pos-cashier-shifts.show');
        Route::post('pos-cashier-shifts/{posCashierShift}/close', [PosCashierShiftController::class, 'close'])->middleware('permission:update-pos-cashier-shift')->name('pos-cashier-shifts.close');
        Route::delete('pos-cashier-shifts/{posCashierShift}', [PosCashierShiftController::class, 'destroy'])->middleware('permission:delete-pos-cashier-shift')->name('pos-cashier-shifts.destroy');

        Route::get('pos-orders', [PosOrderController::class, 'index'])->middleware('permission:read-pos-order')->name('pos-orders.index');
        Route::post('pos-orders', [PosOrderController::class, 'store'])->middleware('permission:create-pos-order')->name('pos-orders.store');
        Route::get('pos-orders/{posOrder}', [PosOrderController::class, 'show'])->middleware('permission:show-pos-order')->name('pos-orders.show');
        Route::post('pos-orders/{posOrder}/post-to-folio', [PosOrderController::class, 'postToFolio'])->middleware('permission:update-folio')->name('pos-orders.post-to-folio');
        Route::post('pos-orders/{posOrder}/send-to-kitchen', [PosOrderController::class, 'sendToKitchen'])->middleware('permission:update-pos-order')->name('pos-orders.send-to-kitchen');
        Route::post('pos-orders/{posOrder}/mark-kitchen-ready', [PosOrderController::class, 'markKitchenReady'])->middleware('permission:update-pos-order')->name('pos-orders.mark-kitchen-ready');
        Route::post('pos-orders/{posOrder}/void', [PosOrderController::class, 'void'])->middleware('permission:update-pos-order')->name('pos-orders.void');
        Route::post('pos-orders/{posOrder}/wastage', [PosOrderController::class, 'recordWastage'])->middleware('permission:update-pos-order')->name('pos-orders.wastage');
        Route::delete('pos-orders/{posOrder}', [PosOrderController::class, 'destroy'])->middleware('permission:delete-pos-order')->name('pos-orders.destroy');

        Route::get('suppliers', [SupplierController::class, 'index'])->middleware('permission:read-supplier')->name('suppliers.index');
        Route::post('suppliers', [SupplierController::class, 'store'])->middleware('permission:create-supplier')->name('suppliers.store');
        Route::get('suppliers/{supplier}', [SupplierController::class, 'show'])->middleware('permission:show-supplier')->name('suppliers.show');
        Route::match(['put', 'patch'], 'suppliers/{supplier}', [SupplierController::class, 'update'])->middleware('permission:update-supplier')->name('suppliers.update');
        Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])->middleware('permission:delete-supplier')->name('suppliers.destroy');

        Route::get('supplier-bills', [SupplierBillController::class, 'index'])->middleware('permission:read-supplier-bill')->name('supplier-bills.index');
        Route::post('supplier-bills', [SupplierBillController::class, 'store'])->middleware('permission:create-supplier-bill')->name('supplier-bills.store');
        Route::get('supplier-bills/{supplierBill}', [SupplierBillController::class, 'show'])->middleware('permission:show-supplier-bill')->name('supplier-bills.show');
        Route::post('supplier-bills/{supplierBill}/payments', [SupplierPaymentController::class, 'store'])->middleware('permission:create-supplier-payment')->name('supplier-bills.payments.store');
    });