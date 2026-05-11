<?php

namespace App\Services;

use App\Domain\Accounting\Models\BankReconciliation;
use App\Domain\Accounting\Models\Invoice;
use App\Domain\Accounting\Models\Payment;
use App\Domain\Accounting\Models\Refund;
use App\Domain\Accounting\Models\SupplierBill;
use App\Domain\Accounting\Models\SupplierPayment;
use App\Domain\Accounting\Services\AccountsPayableService;
use App\Domain\Accounting\Services\AccountsReceivableService;
use App\Domain\Accounting\Services\BankReconciliationService;
use App\Domain\Hotel\Models\Folio;
use App\Domain\Hotel\Models\HousekeepingTask;
use App\Domain\Hotel\Models\MaintenanceRequest;
use App\Domain\Hotel\Models\PosCashierShift;
use App\Domain\Hotel\Models\PosOrder;
use App\Domain\Hotel\Models\PreventiveMaintenanceSchedule;
use App\Domain\Hotel\Models\Reservation;
use App\Domain\Hotel\Models\Room;
use App\Domain\Hotel\Services\FolioService;
use App\Domain\Hotel\Services\HousekeepingService;
use App\Domain\Hotel\Services\MaintenanceService;
use App\Domain\Hotel\Services\PosService;
use App\Domain\Hotel\Services\ReservationOperationsService;
use App\Domain\Inventory\Models\PurchaseOrder;
use App\Domain\Inventory\Models\InventoryMovement;
use App\Domain\Inventory\Services\InventoryService;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TenantWorkspaceRecordService
{
    public function __construct(
        protected TenantWorkspaceService $tenantWorkspaceService,
        protected ReservationOperationsService $reservationOperationsService,
        protected FolioService $folioService,
        protected AccountsReceivableService $accountsReceivableService,
        protected AccountsPayableService $accountsPayableService,
        protected InventoryService $inventoryService,
        protected PosService $posService,
        protected MaintenanceService $maintenanceService,
        protected HousekeepingService $housekeepingService,
        protected BankReconciliationService $bankReconciliationService,
    ) {
    }

    public function recordPage(User $user, string $module, int|string $recordId): array
    {
        $moduleEntry = $this->moduleEntry($user, $module);
        $record = $this->resolveRecord($module, $recordId);

        return match ($module) {
            'reservations' => $this->reservationPage($moduleEntry, $record),
            'folios' => $this->folioPage($moduleEntry, $record),
            'invoices' => $this->invoicePage($moduleEntry, $record),
            'supplier-bills' => $this->supplierBillPage($moduleEntry, $record),
            'purchase-orders' => $this->purchaseOrderPage($moduleEntry, $record),
            'pos-orders' => $this->posOrderPage($moduleEntry, $record),
            'pos-cashier-shifts' => $this->cashierShiftPage($moduleEntry, $record),
            'maintenance-requests' => $this->maintenancePage($moduleEntry, $record),
            'housekeeping-tasks' => $this->housekeepingPage($moduleEntry, $record),
            'bank-reconciliations' => $this->bankReconciliationPage($moduleEntry, $record),
            'preventive-maintenance-schedules' => $this->preventiveSchedulePage($moduleEntry, $record),
            'inventory-movements' => $this->inventoryMovementPage($moduleEntry, $record),
            default => $this->genericPage($moduleEntry, $record),
        };
    }

    public function performAction(User $user, string $module, int|string $recordId, string $action, Request $request): array
    {
        $record = $this->resolveRecord($module, $recordId);

        return match ($module) {
            'reservations' => $this->performReservationAction($user, $record, $action, $request),
            'folios' => $this->performFolioAction($user, $record, $action, $request),
            'invoices' => $this->performInvoiceAction($user, $record, $action, $request),
            'supplier-bills' => $this->performSupplierBillAction($user, $record, $action, $request),
            'purchase-orders' => $this->performPurchaseOrderAction($user, $record, $action, $request),
            'pos-orders' => $this->performPosOrderAction($user, $record, $action, $request),
            'pos-cashier-shifts' => $this->performCashierShiftAction($user, $record, $action, $request),
            'maintenance-requests' => $this->performMaintenanceAction($user, $record, $action, $request),
            'housekeeping-tasks' => $this->performHousekeepingAction($user, $record, $action, $request),
            'bank-reconciliations' => $this->performBankReconciliationAction($user, $record, $action, $request),
            'preventive-maintenance-schedules' => $this->performPreventiveScheduleAction($user, $record, $action),
            default => throw new HttpException(404),
        };
    }

    protected function moduleEntry(User $user, string $module): array
    {
        return collect($this->tenantWorkspaceService->moduleCatalog($user))
            ->first(fn (array $entry) => $entry['key'] === $module)
            ?? throw new HttpException(404);
    }

    protected function resolveRecord(string $module, int|string $recordId): Model
    {
        $record = $this->recordQuery($module)->whereKey($recordId)->first();

        if (! $record) {
            throw new HttpException(404);
        }

        return $record;
    }

    protected function recordQuery(string $module)
    {
        return match ($module) {
            'reservations' => Reservation::query()->with(['property', 'room', 'guestProfile', 'roomMoves.fromRoom', 'roomMoves.toRoom', 'visitors', 'identityDocuments']),
            'folios' => Folio::query()->with(['reservation.guestProfile', 'lines', 'invoices']),
            'invoices' => Invoice::query()->with(['guestProfile', 'folio', 'lines', 'payments', 'refunds']),
            'supplier-bills' => SupplierBill::query()->with(['supplier', 'purchaseOrder', 'lines.purchaseOrderLine.item', 'payments']),
            'purchase-orders' => PurchaseOrder::query()->with(['supplier', 'approver', 'approvals.approver', 'lines.item', 'receipts.lines']),
            'pos-orders' => PosOrder::query()->with(['property', 'shift.cashier', 'reservation.guestProfile', 'folio', 'lines.inventoryItem']),
            'pos-cashier-shifts' => PosCashierShift::query()->with(['property', 'cashier', 'orders.lines']),
            'maintenance-requests' => MaintenanceRequest::query()->with(['property', 'room', 'reporter', 'assignee', 'preventiveSchedule']),
            'housekeeping-tasks' => HousekeepingTask::query()->with(['property', 'room', 'reservation', 'assignee', 'inspector', 'inspections']),
            'bank-reconciliations' => BankReconciliation::query()->with(['bankAccount', 'lines']),
            'preventive-maintenance-schedules' => PreventiveMaintenanceSchedule::query()->with(['property', 'room', 'assignee', 'maintenanceRequests']),
            'inventory-movements' => InventoryMovement::query()->with('item'),
            default => $this->genericRecordQuery($module),
        };
    }

    protected function genericRecordQuery(string $module)
    {
        $modelClass = match ($module) {
            'properties' => \App\Domain\Hotel\Models\Property::class,
            'rooms' => \App\Domain\Hotel\Models\Room::class,
            'guests' => \App\Domain\Hotel\Models\GuestProfile::class,
            'payments' => Payment::class,
            'refunds' => Refund::class,
            'supplier-payments' => SupplierPayment::class,
            'bank-accounts' => \App\Domain\Accounting\Models\BankAccount::class,
            'inventory-items' => \App\Domain\Inventory\Models\InventoryItem::class,
            'inventory-movements' => InventoryMovement::class,
            default => throw new HttpException(404),
        };

        return $modelClass::query();
    }

    protected function genericPage(array $module, Model $record): array
    {
        $attributes = collect($record->getAttributes())
            ->reject(fn (mixed $value, string $key) => in_array($key, ['id', 'company_id'], true) || $value === null)
            ->take(12)
            ->map(fn (mixed $value, string $key) => [
                'label' => str($key)->replace('_', ' ')->headline()->toString(),
                'value' => is_bool($value) ? ($value ? 'Yes' : 'No') : (string) $value,
            ])
            ->values()
            ->all();

        return [
            'module' => $module,
            'record' => $record,
            'title' => $this->recordTitle($record),
            'subtitle' => $module['description'],
            'status' => $record->status ?? null,
            'edit_url' => $this->editableModules()->contains($module['key'])
                ? route('admin.workspace.records.edit', ['module' => $module['key'], 'record' => $record->getKey()])
                : null,
            'metrics' => [],
            'detailSections' => [
                [
                    'title' => 'Record details',
                    'items' => $attributes,
                ],
            ],
            'tables' => [],
            'actionForms' => [],
        ];
    }

    protected function reservationPage(array $module, Reservation $reservation): array
    {
        return [
            'module' => $module,
            'record' => $reservation,
            'title' => $reservation->reservation_number,
            'subtitle' => trim(($reservation->guestProfile?->first_name ?? '').' '.($reservation->guestProfile?->last_name ?? '')),
            'status' => $reservation->status,
            'metrics' => [
                $this->metric('Room', $reservation->room?->room_number ?: 'Unassigned'),
                $this->metric('Stay', $reservation->check_in_date?->toDateString().' to '.$reservation->check_out_date?->toDateString()),
                $this->metric('Total amount', number_format((float) $reservation->total_amount, 2)),
            ],
            'detailSections' => [
                [
                    'title' => 'Reservation details',
                    'items' => [
                        $this->detail('Property', $reservation->property?->name ?: 'Not linked'),
                        $this->detail('Booking source', $reservation->booking_source ?: 'Not provided'),
                        $this->detail('Adults', (string) $reservation->adult_count),
                        $this->detail('Children', (string) ($reservation->child_count ?? 0)),
                        $this->detail('Special requests', $reservation->special_requests ?: 'None'),
                        $this->detail('Pre-arrival', str($reservation->pre_arrival_status ?? 'pending')->headline()->toString()),
                    ],
                ],
                [
                    'title' => 'Guest compliance',
                    'items' => [
                        $this->detail('Signature', $reservation->check_in_signature_name ?: 'Not captured'),
                        $this->detail('Signed at', $reservation->signed_registration_at?->format('Y-m-d H:i') ?: 'Not captured'),
                        $this->detail('ID verified at', $reservation->id_verified_at?->format('Y-m-d H:i') ?: 'Not verified'),
                        $this->detail('Visitor count', (string) $reservation->visitors->count()),
                    ],
                ],
            ],
            'tables' => [
                $this->table('Room moves', ['From', 'To', 'Moved at', 'Reason'], $reservation->roomMoves->map(fn ($move) => [
                    $move->fromRoom?->room_number ?: 'Unknown',
                    $move->toRoom?->room_number ?: 'Unknown',
                    $move->moved_at?->format('Y-m-d H:i') ?: 'Pending',
                    $move->reason ?: 'Not provided',
                ])->all()),
                $this->table('Visitors', ['Name', 'Relationship', 'Phone', 'Checked in'], $reservation->visitors->map(fn ($visitor) => [
                    $visitor->full_name,
                    $visitor->relationship_to_guest ?: 'Not provided',
                    $visitor->phone ?: 'Not provided',
                    $visitor->checked_in_at?->format('Y-m-d H:i') ?: 'Pending',
                ])->all()),
            ],
            'actionForms' => $this->reservationActionForms($reservation),
        ];
    }

    protected function folioPage(array $module, Folio $folio): array
    {
        return [
            'module' => $module,
            'record' => $folio,
            'title' => $folio->folio_number,
            'subtitle' => $folio->reservation?->reservation_number ?: 'Open guest ledger',
            'status' => $folio->status,
            'metrics' => [
                $this->metric('Subtotal', number_format((float) $folio->subtotal_amount, 2)),
                $this->metric('Tax', number_format((float) $folio->tax_amount, 2)),
                $this->metric('Balance', number_format((float) $folio->balance_amount, 2)),
            ],
            'detailSections' => [[
                'title' => 'Folio details',
                'items' => [
                    $this->detail('Reservation', $folio->reservation?->reservation_number ?: 'Not linked'),
                    $this->detail('Guest', trim(($folio->reservation?->guestProfile?->first_name ?? '').' '.($folio->reservation?->guestProfile?->last_name ?? '')) ?: 'Not linked'),
                    $this->detail('Currency', $folio->currency_code),
                    $this->detail('Opened at', $folio->opened_at?->format('Y-m-d H:i') ?: 'Pending'),
                ],
            ]],
            'tables' => [
                $this->table('Folio lines', ['Description', 'Type', 'Qty', 'Unit price', 'Tax', 'Total'], $folio->lines->map(fn ($line) => [
                    $line->description,
                    str($line->line_type)->headline()->toString(),
                    number_format((float) $line->quantity, 2),
                    number_format((float) $line->unit_price, 2),
                    number_format((float) $line->tax_amount, 2),
                    number_format((float) $line->total_amount, 2),
                ])->all()),
                $this->table('Invoices', ['Invoice', 'Status', 'Issue date', 'Balance'], $folio->invoices->map(fn ($invoice) => [
                    $invoice->invoice_number,
                    str($invoice->status)->headline()->toString(),
                    $invoice->issue_date?->toDateString() ?: 'Not set',
                    number_format((float) $invoice->balance_amount, 2),
                ])->all()),
            ],
            'actionForms' => $this->folioActionForms($folio),
        ];
    }

    protected function invoicePage(array $module, Invoice $invoice): array
    {
        $issueDate = $invoice->issue_date instanceof \DateTimeInterface ? $invoice->issue_date->format('Y-m-d') : ((string) ($invoice->issue_date ?: 'Not set'));
        $dueDate = $invoice->due_date instanceof \DateTimeInterface ? $invoice->due_date->format('Y-m-d') : ((string) ($invoice->due_date ?: 'Not set'));

        return [
            'module' => $module,
            'record' => $invoice,
            'title' => $invoice->invoice_number,
            'subtitle' => trim(($invoice->guestProfile?->first_name ?? '').' '.($invoice->guestProfile?->last_name ?? '')) ?: 'Tenant receivable',
            'status' => $invoice->status,
            'metrics' => [
                $this->metric('Total', number_format((float) $invoice->total_amount, 2)),
                $this->metric('Balance', number_format((float) $invoice->balance_amount, 2)),
                $this->metric('Payments', (string) $invoice->payments->count()),
            ],
            'detailSections' => [[
                'title' => 'Invoice details',
                'items' => [
                    $this->detail('Folio', $invoice->folio?->folio_number ?: 'Not linked'),
                    $this->detail('Issue date', $issueDate),
                    $this->detail('Due date', $dueDate),
                    $this->detail('Currency', $invoice->currency_code),
                    $this->detail('Notes', $invoice->notes ?: 'None'),
                ],
            ]],
            'tables' => [
                $this->table('Invoice lines', ['Description', 'Qty', 'Unit price', 'Tax', 'Total'], $invoice->lines->map(fn ($line) => [
                    $line->description,
                    number_format((float) $line->quantity, 2),
                    number_format((float) $line->unit_price, 2),
                    number_format((float) $line->tax_amount, 2),
                    number_format((float) $line->total_amount, 2),
                ])->all()),
                $this->table('Payments', ['Payment', 'Method', 'Paid at', 'Amount'], $invoice->payments->map(fn ($payment) => [
                    $payment->payment_number,
                    str($payment->payment_method)->headline()->toString(),
                    $payment->paid_at?->format('Y-m-d H:i') ?: 'Pending',
                    number_format((float) $payment->amount, 2),
                ])->all()),
                $this->table('Refunds', ['Refund', 'Refunded at', 'Amount', 'Reason'], $invoice->refunds->map(fn ($refund) => [
                    $refund->refund_number,
                    $refund->refunded_at?->format('Y-m-d H:i') ?: 'Pending',
                    number_format((float) $refund->amount, 2),
                    $refund->reason ?: 'Not provided',
                ])->all()),
            ],
            'actionForms' => $this->invoiceActionForms($invoice),
        ];
    }

    protected function inventoryMovementPage(array $module, InventoryMovement $movement): array
    {
        return [
            'module' => $module,
            'record' => $movement,
            'title' => str($movement->movement_type)->headline()->toString().' movement',
            'subtitle' => $movement->item?->name ?: 'Inventory movement',
            'status' => null,
            'metrics' => [
                $this->metric('Quantity change', number_format((float) $movement->quantity_change, 2)),
                $this->metric('Unit cost', number_format((float) ($movement->unit_cost ?? 0), 2)),
                $this->metric('Moved at', $movement->moved_at?->format('Y-m-d H:i') ?: 'Pending'),
            ],
            'detailSections' => [[
                'title' => 'Movement details',
                'items' => [
                    $this->detail('Item', $movement->item?->name ?: 'Unknown item'),
                    $this->detail('Movement type', str($movement->movement_type)->headline()->toString()),
                    $this->detail('Source type', $movement->source_type ?: 'Not linked'),
                    $this->detail('Source ID', $movement->source_id ? (string) $movement->source_id : 'Not linked'),
                    $this->detail('Notes', $movement->notes ?: 'None'),
                ],
            ]],
            'tables' => [],
            'actionForms' => [],
        ];
    }

    protected function supplierBillPage(array $module, SupplierBill $bill): array
    {
        $billDate = $bill->bill_date instanceof \DateTimeInterface ? $bill->bill_date->format('Y-m-d') : ((string) ($bill->bill_date ?: 'Not set'));
        $dueDate = $bill->due_date instanceof \DateTimeInterface ? $bill->due_date->format('Y-m-d') : ((string) ($bill->due_date ?: 'Not set'));

        return [
            'module' => $module,
            'record' => $bill,
            'title' => $bill->bill_number,
            'subtitle' => $bill->supplier?->name ?: 'Supplier payable',
            'status' => $bill->status,
            'metrics' => [
                $this->metric('Total', number_format((float) $bill->total_amount, 2)),
                $this->metric('Balance', number_format((float) $bill->balance_amount, 2)),
                $this->metric('Match', str($bill->match_status)->headline()->toString()),
            ],
            'detailSections' => [[
                'title' => 'Supplier bill details',
                'items' => [
                    $this->detail('Purchase order', $bill->purchaseOrder?->purchase_order_number ?: 'Not linked'),
                    $this->detail('Bill date', $billDate),
                    $this->detail('Due date', $dueDate),
                    $this->detail('Description', $bill->description ?: 'None'),
                ],
            ]],
            'tables' => [
                $this->table('Bill lines', ['Description', 'Inventory item', 'PO line', 'Qty', 'Total'], $bill->lines->map(fn ($line) => [
                    $line->description,
                    $line->inventoryItem?->name ?: 'Not linked',
                    $line->purchaseOrderLine?->description ?: 'Not linked',
                    number_format((float) $line->quantity, 2),
                    number_format((float) $line->total_amount, 2),
                ])->all()),
                $this->table('Payments', ['Payment', 'Method', 'Paid at', 'Amount'], $bill->payments->map(fn ($payment) => [
                    $payment->payment_number,
                    str($payment->payment_method)->headline()->toString(),
                    $payment->paid_at?->format('Y-m-d H:i') ?: 'Pending',
                    number_format((float) $payment->amount, 2),
                ])->all()),
            ],
            'actionForms' => $this->supplierBillActionForms($bill),
        ];
    }

    protected function purchaseOrderPage(array $module, PurchaseOrder $order): array
    {
        $orderDate = $order->order_date instanceof \DateTimeInterface ? $order->order_date->format('Y-m-d') : ((string) ($order->order_date ?: 'Not set'));
        $expectedDeliveryDate = $order->expected_delivery_date instanceof \DateTimeInterface ? $order->expected_delivery_date->format('Y-m-d') : ((string) ($order->expected_delivery_date ?: 'Not set'));

        return [
            'module' => $module,
            'record' => $order,
            'title' => $order->purchase_order_number,
            'subtitle' => $order->supplier?->name ?: 'Supplier pending',
            'status' => $order->status,
            'metrics' => [
                $this->metric('Total', number_format((float) $order->total_amount, 2)),
                $this->metric('Match', str($order->match_status)->headline()->toString()),
                $this->metric('Approvals', (string) $order->approvals->count()),
            ],
            'detailSections' => [[
                'title' => 'Purchase order details',
                'items' => [
                    $this->detail('Order date', $orderDate),
                    $this->detail('Expected delivery', $expectedDeliveryDate),
                    $this->detail('Quantity tolerance', number_format((float) $order->quantity_tolerance_percent, 2).' %'),
                    $this->detail('Amount tolerance', number_format((float) $order->amount_tolerance_percent, 2).' %'),
                    $this->detail('Approved by', $order->approver?->name ?: 'Not approved'),
                    $this->detail('Notes', $order->notes ?: 'None'),
                ],
            ]],
            'tables' => [
                $this->table('Approval chain', ['Sequence', 'Approver', 'Status', 'Acted at', 'Notes'], $order->approvals->map(fn ($approval) => [
                    (string) $approval->sequence_number,
                    $approval->approver?->name ?: 'Unknown',
                    str($approval->status)->headline()->toString(),
                    $approval->acted_at?->format('Y-m-d H:i') ?: 'Pending',
                    $approval->notes ?: 'None',
                ])->all()),
                $this->table('Line items', ['Item', 'Ordered', 'Received', 'Billed', 'Unit cost', 'Total'], $order->lines->map(fn ($line) => [
                    $line->item?->name ?: $line->description,
                    number_format((float) $line->ordered_quantity, 2),
                    number_format((float) $line->received_quantity, 2),
                    number_format((float) $line->billed_quantity, 2),
                    number_format((float) $line->unit_cost, 2),
                    number_format((float) $line->total_amount, 2),
                ])->all()),
            ],
            'actionForms' => $this->purchaseOrderActionForms($order),
        ];
    }

    protected function posOrderPage(array $module, PosOrder $order): array
    {
        return [
            'module' => $module,
            'record' => $order,
            'title' => $order->order_number,
            'subtitle' => $order->property?->name ?: 'POS order',
            'status' => $order->status,
            'metrics' => [
                $this->metric('Total', number_format((float) $order->total_amount, 2)),
                $this->metric('Charge to room', $order->charge_to_room ? 'Yes' : 'No'),
                $this->metric('Kitchen lines', (string) $order->lines->whereNotNull('kitchen_station')->count()),
            ],
            'detailSections' => [[
                'title' => 'POS order details',
                'items' => [
                    $this->detail('Cashier shift', $order->shift?->shift_number ?: 'Not linked'),
                    $this->detail('Reservation', $order->reservation?->reservation_number ?: 'Not linked'),
                    $this->detail('Folio', $order->folio?->folio_number ?: 'Not linked'),
                    $this->detail('Service location', str($order->service_location ?? 'unspecified')->headline()->toString()),
                    $this->detail('Payment method', str($order->payment_method ?? 'unpaid')->headline()->toString()),
                    $this->detail('Notes', $order->notes ?: 'None'),
                ],
            ]],
            'tables' => [
                $this->table('POS lines', ['Item', 'Kitchen', 'Status', 'Qty', 'Modifiers', 'Total'], $order->lines->map(fn ($line) => [
                    $line->item_name,
                    $line->kitchen_station ?: 'None',
                    str($line->kitchen_status)->headline()->toString(),
                    number_format((float) $line->quantity, 2),
                    collect($line->modifiers ?? [])->map(fn (array $modifier) => ($modifier['name'] ?? 'Modifier').' x'.($modifier['quantity'] ?? 1))->implode(', ') ?: 'None',
                    number_format((float) $line->total_amount, 2),
                ])->all()),
            ],
            'actionForms' => $this->posOrderActionForms($order),
        ];
    }

    protected function cashierShiftPage(array $module, PosCashierShift $shift): array
    {
        $orders = $shift->orders instanceof Collection ? $shift->orders : collect($shift->orders ?? []);

        return [
            'module' => $module,
            'record' => $shift,
            'title' => $shift->shift_number,
            'subtitle' => $shift->property?->name ?: 'Cashier shift',
            'status' => $shift->status,
            'metrics' => [
                $this->metric('Opening cash', number_format((float) $shift->opening_cash_amount, 2)),
                $this->metric('Expected cash', number_format((float) $shift->expected_cash_amount, 2)),
                $this->metric('Orders', (string) $orders->count()),
            ],
            'detailSections' => [[
                'title' => 'Shift details',
                'items' => [
                    $this->detail('Cashier', $shift->cashier?->name ?: 'Unknown'),
                    $this->detail('Opened at', $shift->opened_at?->format('Y-m-d H:i') ?: 'Pending'),
                    $this->detail('Closed at', $shift->closed_at?->format('Y-m-d H:i') ?: 'Not closed'),
                    $this->detail('Variance', number_format((float) $shift->variance_amount, 2)),
                    $this->detail('Notes', $shift->notes ?: 'None'),
                ],
            ]],
            'tables' => [
                $this->table('Orders', ['Order', 'Status', 'Payment', 'Total'], $orders->map(fn ($order) => [
                    $order->order_number,
                    str($order->status)->headline()->toString(),
                    str($order->payment_method ?? 'unpaid')->headline()->toString(),
                    number_format((float) $order->total_amount, 2),
                ])->all()),
            ],
            'actionForms' => $this->cashierShiftActionForms($shift),
        ];
    }

    protected function maintenancePage(array $module, MaintenanceRequest $request): array
    {
        return [
            'module' => $module,
            'record' => $request,
            'title' => $request->title,
            'subtitle' => $request->property?->name ?: 'Maintenance request',
            'status' => $request->status,
            'edit_url' => route('admin.workspace.records.edit', ['module' => $module['key'], 'record' => $request->getKey()]),
            'metrics' => [
                $this->metric('Priority', str($request->priority)->headline()->toString()),
                $this->metric('Room', $request->room?->room_number ?: 'Common area'),
                $this->metric('Preventive', $request->is_preventive ? 'Yes' : 'No'),
            ],
            'detailSections' => [[
                'title' => 'Maintenance details',
                'items' => [
                    $this->detail('Reporter', $request->reporter?->name ?: 'Unknown'),
                    $this->detail('Assignee', $request->assignee?->name ?: 'Unassigned'),
                    $this->detail('Category', str($request->maintenance_category ?? 'general')->headline()->toString()),
                    $this->detail('Scheduled for', $request->scheduled_for?->format('Y-m-d H:i') ?: 'Not scheduled'),
                    $this->detail('Technician notes', $request->technician_notes ?: 'None'),
                    $this->detail('Description', $request->description ?: 'None'),
                ],
            ]],
            'tables' => [],
            'actionForms' => $this->maintenanceActionForms($request),
        ];
    }

    protected function housekeepingPage(array $module, HousekeepingTask $task): array
    {
        return [
            'module' => $module,
            'record' => $task,
            'title' => str($task->task_type)->headline()->toString(),
            'subtitle' => $task->room?->room_number ? 'Room '.$task->room->room_number : 'Housekeeping task',
            'status' => $task->status,
            'edit_url' => route('admin.workspace.records.edit', ['module' => $module['key'], 'record' => $task->getKey()]),
            'metrics' => [
                $this->metric('Priority', str($task->priority ?? 'standard')->headline()->toString()),
                $this->metric('Linen', str($task->linen_status ?? 'not_required')->headline()->toString()),
                $this->metric('Minibar', str($task->minibar_status ?? 'not_checked')->headline()->toString()),
            ],
            'detailSections' => [[
                'title' => 'Housekeeping details',
                'items' => [
                    $this->detail('Assignee', $task->assignee?->name ?: 'Unassigned'),
                    $this->detail('Inspector', $task->inspector?->name ?: 'Not assigned'),
                    $this->detail('Scheduled for', $task->scheduled_for?->format('Y-m-d H:i') ?: 'Not scheduled'),
                    $this->detail('Inspection status', str($task->inspection_status ?? 'pending')->headline()->toString()),
                    $this->detail('Notes', $task->notes ?: 'None'),
                ],
            ]],
            'tables' => [
                $this->table('Inspections', ['Status', 'Inspector', 'Inspected at'], $task->inspections->map(fn ($inspection) => [
                    str($inspection->status)->headline()->toString(),
                    $inspection->inspector?->name ?? 'Unknown',
                    $inspection->inspected_at?->format('Y-m-d H:i') ?: 'Pending',
                ])->all()),
            ],
            'actionForms' => $this->housekeepingActionForms($task),
        ];
    }

    protected function bankReconciliationPage(array $module, BankReconciliation $reconciliation): array
    {
        return [
            'module' => $module,
            'record' => $reconciliation,
            'title' => ($reconciliation->bankAccount?->name ?: 'Bank account').' reconciliation',
            'subtitle' => ($reconciliation->period_start?->toDateString() ?: 'N/A').' to '.($reconciliation->period_end?->toDateString() ?: 'N/A'),
            'status' => $reconciliation->status,
            'metrics' => [
                $this->metric('Statement balance', number_format((float) $reconciliation->statement_ending_balance, 2)),
                $this->metric('Book balance', number_format((float) $reconciliation->book_ending_balance, 2)),
                $this->metric('Cleared balance', number_format((float) $reconciliation->cleared_balance, 2)),
            ],
            'detailSections' => [[
                'title' => 'Reconciliation details',
                'items' => [
                    $this->detail('Bank account', $reconciliation->bankAccount?->name ?: 'Unknown'),
                    $this->detail('Completed at', $reconciliation->completed_at?->format('Y-m-d H:i') ?: 'Open'),
                    $this->detail('Notes', $reconciliation->notes ?: 'None'),
                ],
            ]],
            'tables' => [
                $this->table('Reconciliation lines', ['Entry type', 'Description', 'Date', 'Amount', 'Cleared'], $reconciliation->lines->map(fn ($line) => [
                    str($line->entry_type)->headline()->toString(),
                    $line->description,
                    $line->transaction_date?->toDateString() ?: 'Not set',
                    number_format((float) $line->amount, 2),
                    $line->is_cleared ? 'Yes' : 'No',
                ])->all()),
            ],
            'actionForms' => $this->bankReconciliationActionForms($reconciliation),
        ];
    }

    protected function preventiveSchedulePage(array $module, PreventiveMaintenanceSchedule $schedule): array
    {
        return [
            'module' => $module,
            'record' => $schedule,
            'title' => $schedule->title,
            'subtitle' => $schedule->property?->name ?: 'Preventive maintenance schedule',
            'status' => $schedule->is_active ? 'active' : 'inactive',
            'edit_url' => route('admin.workspace.records.edit', ['module' => $module['key'], 'record' => $schedule->getKey()]),
            'metrics' => [
                $this->metric('Frequency', $schedule->frequency_days.' days'),
                $this->metric('Next due', $schedule->next_due_at?->format('Y-m-d H:i') ?: 'Not scheduled'),
                $this->metric('Generated requests', (string) $schedule->maintenanceRequests->count()),
            ],
            'detailSections' => [[
                'title' => 'Schedule details',
                'items' => [
                    $this->detail('Room', $schedule->room?->room_number ?: 'Common area'),
                    $this->detail('Assignee', $schedule->assignee?->name ?: 'Unassigned'),
                    $this->detail('Category', str($schedule->maintenance_category ?? 'general')->headline()->toString()),
                    $this->detail('Priority', str($schedule->priority ?? 'medium')->headline()->toString()),
                    $this->detail('Notes', $schedule->notes ?: 'None'),
                ],
            ]],
            'tables' => [
                $this->table('Generated requests', ['Request', 'Status', 'Reported at'], $schedule->maintenanceRequests->map(fn ($request) => [
                    $request->title,
                    str($request->status)->headline()->toString(),
                    $request->reported_at?->format('Y-m-d H:i') ?: 'Pending',
                ])->all()),
            ],
            'actionForms' => $this->preventiveScheduleActionForms($schedule),
        ];
    }

    protected function reservationActionForms(Reservation $reservation): array
    {
        $actions = [];

        if (in_array($reservation->status, [Reservation::STATUS_PENDING, Reservation::STATUS_CONFIRMED], true)) {
            $actions[] = $this->actionForm($reservation, 'reservations', 'pre-arrival', 'Pre-arrival registration', 'Capture expected arrival and registration confirmation.', [
                $this->field('expected_arrival_time', 'Expected arrival', 'datetime-local', now()->format('Y-m-d\TH:i')),
                $this->field('signature_name', 'Signature name'),
                $this->field('special_requests', 'Special requests', 'textarea'),
            ], 'Save pre-arrival');
            $actions[] = $this->actionForm($reservation, 'reservations', 'check-in', 'Check in guest', 'Move the stay into in-house status and open the folio.', [
                $this->field('actual_check_in_at', 'Actual check-in', 'datetime-local', now()->format('Y-m-d\TH:i')),
                $this->field('signature_name', 'Signature name'),
            ], 'Check in');
        }

        if ($reservation->status === Reservation::STATUS_CHECKED_IN) {
            $actions[] = $this->actionForm($reservation, 'reservations', 'check-out', 'Check out guest', 'Close the stay and trigger housekeeping.', [
                $this->field('actual_check_out_at', 'Actual check-out', 'datetime-local', now()->format('Y-m-d\TH:i')),
                $this->field('housekeeping_notes', 'Housekeeping notes', 'textarea'),
            ], 'Check out');
            $actions[] = $this->actionForm($reservation, 'reservations', 'move-room', 'Move room', 'Relocate the in-house guest to a different room.', [
                $this->field('to_room_id', 'New room', 'select', null, Room::query()->where('property_id', $reservation->property_id)->orderBy('room_number')->get()->map(fn (Room $room) => ['value' => $room->id, 'label' => 'Room '.$room->room_number])->all(), true),
                $this->field('reason', 'Reason', 'textarea'),
            ], 'Move room');
        }

        return $actions;
    }

    protected function folioActionForms(Folio $folio): array
    {
        return [
            $this->actionForm($folio, 'folios', 'add-charge', 'Add folio charge', 'Post a new charge line directly to the guest ledger.', [
                $this->field('description', 'Description', 'text', '', [], true),
                $this->field('line_type', 'Line type', 'text', 'room_charge'),
                $this->field('quantity', 'Quantity', 'number', '1', [], true),
                $this->field('unit_price', 'Unit price', 'number', '0', [], true),
                $this->field('tax_amount', 'Tax amount', 'number', '0'),
                $this->field('service_date', 'Service date', 'date', now()->toDateString()),
            ], 'Add charge'),
            $this->actionForm($folio, 'folios', 'issue-invoice', 'Issue invoice', 'Convert the open folio into an accounts receivable document.', [
                $this->field('issue_date', 'Issue date', 'date', now()->toDateString()),
                $this->field('due_date', 'Due date', 'date', now()->toDateString()),
                $this->field('notes', 'Notes', 'textarea'),
            ], 'Issue invoice'),
        ];
    }

    protected function invoiceActionForms(Invoice $invoice): array
    {
        $actions = [];

        if ((float) $invoice->balance_amount > 0 && $invoice->status !== Invoice::STATUS_VOID) {
            $actions[] = $this->actionForm($invoice, 'invoices', 'post-payment', 'Post payment', 'Apply a guest payment against this invoice.', [
                $this->field('payment_method', 'Payment method', 'text', 'cash'),
                $this->field('paid_at', 'Paid at', 'datetime-local', now()->format('Y-m-d\TH:i')),
                $this->field('amount', 'Amount', 'number', number_format((float) $invoice->balance_amount, 2, '.', ''), [], true),
                $this->field('reference', 'Reference'),
            ], 'Post payment');
        }

        $actions[] = $this->actionForm($invoice, 'invoices', 'post-refund', 'Post refund', 'Record a refund linked to this invoice.', [
            $this->field('payment_id', 'Payment', 'select', null, $invoice->payments->map(fn ($payment) => ['value' => $payment->id, 'label' => $payment->payment_number.' · '.number_format((float) $payment->amount, 2)])->all()),
            $this->field('refunded_at', 'Refunded at', 'datetime-local', now()->format('Y-m-d\TH:i')),
            $this->field('amount', 'Amount', 'number', '0', [], true),
            $this->field('reason', 'Reason', 'textarea'),
        ], 'Post refund');

        return $actions;
    }

    protected function supplierBillActionForms(SupplierBill $bill): array
    {
        if ((float) $bill->balance_amount <= 0 || $bill->status === SupplierBill::STATUS_VOID) {
            return [];
        }

        return [
            $this->actionForm($bill, 'supplier-bills', 'post-payment', 'Post supplier payment', 'Apply a settlement against this supplier bill.', [
                $this->field('payment_method', 'Payment method', 'text', 'bank_transfer'),
                $this->field('paid_at', 'Paid at', 'datetime-local', now()->format('Y-m-d\TH:i')),
                $this->field('amount', 'Amount', 'number', number_format((float) $bill->balance_amount, 2, '.', ''), [], true),
                $this->field('reference', 'Reference'),
            ], 'Post payment'),
        ];
    }

    protected function purchaseOrderActionForms(PurchaseOrder $order): array
    {
        $actions = [];

        if (! in_array($order->status, [PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_RECEIVED, PurchaseOrder::STATUS_PARTIALLY_RECEIVED, PurchaseOrder::STATUS_CANCELLED, PurchaseOrder::STATUS_REJECTED], true)) {
            $actions[] = $this->actionForm($order, 'purchase-orders', 'approve', 'Approve purchase order', 'Advance the PO through the approval chain.', [
                $this->field('notes', 'Notes', 'textarea'),
            ], 'Approve');
            $actions[] = $this->actionForm($order, 'purchase-orders', 'reject', 'Reject purchase order', 'Reject this PO from the current approval step.', [
                $this->field('notes', 'Notes', 'textarea'),
            ], 'Reject');
        }

        if (in_array($order->status, [PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_PARTIALLY_RECEIVED], true)) {
            $fields = [
                $this->field('received_at', 'Received at', 'datetime-local', now()->format('Y-m-d\TH:i')),
                $this->field('notes', 'Notes', 'textarea'),
            ];

            foreach ($order->lines as $index => $line) {
                $fields[] = $this->field('lines['.$index.'][purchase_order_line_id]', $line->item?->name ?: $line->description, 'hidden', (string) $line->id);
                $fields[] = $this->field('lines['.$index.'][received_quantity]', 'Received quantity for '.($line->item?->name ?: $line->description), 'number', number_format(max(0, (float) $line->ordered_quantity - (float) $line->received_quantity), 2, '.', ''));
                $fields[] = $this->field('lines['.$index.'][unit_cost]', 'Unit cost for '.($line->item?->name ?: $line->description), 'number', number_format((float) $line->unit_cost, 2, '.', ''));
            }

            $actions[] = $this->actionForm($order, 'purchase-orders', 'receive', 'Receive purchase order', 'Record receipt quantities against each PO line.', $fields, 'Receive');
        }

        return $actions;
    }

    protected function posOrderActionForms(PosOrder $order): array
    {
        $actions = [];

        if ($order->charge_to_room && ! $order->posted_to_folio_at) {
            $actions[] = $this->actionForm($order, 'pos-orders', 'post-to-folio', 'Post to folio', 'Transfer this room charge into the guest folio.', [], 'Post to folio');
        }

        if ($order->lines->contains(fn ($line) => $line->kitchen_station !== null && $line->kitchen_status === \App\Domain\Hotel\Models\PosOrderLine::KITCHEN_STATUS_PENDING)) {
            $actions[] = $this->actionForm($order, 'pos-orders', 'send-to-kitchen', 'Send to kitchen', 'Fire all pending kitchen lines.', [], 'Send');
        }

        if ($order->lines->contains(fn ($line) => $line->kitchen_station !== null && $line->kitchen_status === \App\Domain\Hotel\Models\PosOrderLine::KITCHEN_STATUS_FIRED)) {
            $actions[] = $this->actionForm($order, 'pos-orders', 'mark-kitchen-ready', 'Mark kitchen ready', 'Mark all fired kitchen lines as ready.', [], 'Mark ready');
        }

        if ($order->status !== PosOrder::STATUS_VOID) {
            $actions[] = $this->actionForm($order, 'pos-orders', 'void', 'Void order', 'Void the order and optionally restock inventory.', [
                $this->field('reason', 'Reason', 'textarea', '', [], true),
                $this->field('inventory_disposition', 'Inventory disposition', 'select', 'waste', [
                    ['value' => 'waste', 'label' => 'Waste'],
                    ['value' => 'restock', 'label' => 'Restock'],
                ]),
            ], 'Void order');
        }

        if ($order->lines->contains(fn ($line) => $line->inventory_item_id !== null)) {
            $actions[] = $this->actionForm($order, 'pos-orders', 'record-wastage', 'Record wastage', 'Apply a wastage adjustment to an inventory-backed line.', [
                $this->field('pos_order_line_id', 'Order line', 'select', null, $order->lines->whereNotNull('inventory_item_id')->map(fn ($line) => ['value' => $line->id, 'label' => $line->item_name])->values()->all(), true),
                $this->field('wasted_quantity', 'Wasted quantity', 'number', '0', [], true),
                $this->field('reason', 'Reason', 'textarea'),
            ], 'Record wastage');
        }

        return $actions;
    }

    protected function cashierShiftActionForms(PosCashierShift $shift): array
    {
        if ($shift->status === PosCashierShift::STATUS_CLOSED) {
            return [];
        }

        return [
            $this->actionForm($shift, 'pos-cashier-shifts', 'close', 'Close shift', 'Reconcile the drawer and close the cashier shift.', [
                $this->field('closing_cash_amount', 'Closing cash amount', 'number', number_format((float) $shift->expected_cash_amount, 2, '.', ''), [], true),
                $this->field('notes', 'Notes', 'textarea'),
            ], 'Close shift'),
        ];
    }

    protected function maintenanceActionForms(MaintenanceRequest $request): array
    {
        return [
            $this->actionForm($request, 'maintenance-requests', 'update', 'Update maintenance request', 'Assign, progress, or complete this maintenance request.', [
                $this->field('assigned_to_user_id', 'Assign to', 'select', $request->assigned_to_user_id, User::query()->orderBy('name')->get()->map(fn (User $user) => ['value' => $user->id, 'label' => $user->name])->all()),
                $this->field('status', 'Status', 'select', $request->status, collect([MaintenanceRequest::STATUS_OPEN, MaintenanceRequest::STATUS_IN_PROGRESS, MaintenanceRequest::STATUS_COMPLETED, MaintenanceRequest::STATUS_CANCELLED])->map(fn ($status) => ['value' => $status, 'label' => str($status)->headline()->toString()])->all(), true),
                $this->field('priority', 'Priority', 'select', $request->priority, collect([MaintenanceRequest::PRIORITY_LOW, MaintenanceRequest::PRIORITY_MEDIUM, MaintenanceRequest::PRIORITY_HIGH, MaintenanceRequest::PRIORITY_URGENT])->map(fn ($priority) => ['value' => $priority, 'label' => str($priority)->headline()->toString()])->all(), true),
                $this->field('technician_notes', 'Technician notes', 'textarea', $request->technician_notes),
            ], 'Save maintenance update'),
        ];
    }

    protected function housekeepingActionForms(HousekeepingTask $task): array
    {
        return [
            $this->actionForm($task, 'housekeeping-tasks', 'update', 'Update housekeeping task', 'Assign, progress, inspect, or complete this task.', [
                $this->field('assigned_to_user_id', 'Assign to', 'select', $task->assigned_to_user_id, User::query()->orderBy('name')->get()->map(fn (User $user) => ['value' => $user->id, 'label' => $user->name])->all()),
                $this->field('status', 'Status', 'select', $task->status, collect([HousekeepingTask::STATUS_PENDING, HousekeepingTask::STATUS_IN_PROGRESS, HousekeepingTask::STATUS_COMPLETED, HousekeepingTask::STATUS_INSPECTED])->map(fn ($status) => ['value' => $status, 'label' => str($status)->headline()->toString()])->all(), true),
                $this->field('inspection_status', 'Inspection status', 'select', $task->inspection_status, collect([HousekeepingTask::INSPECTION_STATUS_PASSED, HousekeepingTask::INSPECTION_STATUS_FAILED])->map(fn ($status) => ['value' => $status, 'label' => str($status)->headline()->toString()])->all()),
                $this->field('inspection_notes', 'Inspection notes', 'textarea', $task->inspection_notes),
                $this->field('minibar_status', 'Minibar status', 'select', $task->minibar_status, collect([HousekeepingTask::MINIBAR_STATUS_NOT_CHECKED, HousekeepingTask::MINIBAR_STATUS_PENDING, HousekeepingTask::MINIBAR_STATUS_RESTOCKED])->map(fn ($status) => ['value' => $status, 'label' => str($status)->headline()->toString()])->all()),
            ], 'Save housekeeping update'),
        ];
    }

    protected function bankReconciliationActionForms(BankReconciliation $reconciliation): array
    {
        if ($reconciliation->status === BankReconciliation::STATUS_COMPLETED) {
            return [];
        }

        return [
            $this->actionForm($reconciliation, 'bank-reconciliations', 'complete', 'Complete reconciliation', 'Mark this reconciliation period as completed.', [], 'Complete'),
        ];
    }

    protected function preventiveScheduleActionForms(PreventiveMaintenanceSchedule $schedule): array
    {
        return [
            $this->actionForm($schedule, 'preventive-maintenance-schedules', 'generate', 'Generate maintenance request', 'Create a maintenance request from this schedule.', [], 'Generate request'),
        ];
    }

    protected function performReservationAction(User $user, Reservation $reservation, string $action, Request $request): array
    {
        $this->authorize($user, 'update-reservation');

        match ($action) {
            'pre-arrival' => $this->reservationOperationsService->submitPreArrivalRegistration($reservation, $user, $request->validate([
                'expected_arrival_time' => ['nullable', 'date'],
                'signature_name' => ['nullable', 'string', 'max:255'],
                'special_requests' => ['nullable', 'string'],
            ])),
            'check-in' => $this->reservationOperationsService->checkIn($reservation, $user, $request->validate([
                'actual_check_in_at' => ['nullable', 'date'],
                'signature_name' => ['nullable', 'string', 'max:255'],
            ])),
            'check-out' => $this->reservationOperationsService->checkOut($reservation, $user, $request->validate([
                'actual_check_out_at' => ['nullable', 'date'],
                'housekeeping_notes' => ['nullable', 'string'],
            ])),
            'move-room' => $this->reservationOperationsService->moveRoom(
                $reservation,
                $request->validate([
                    'to_room_id' => ['required', 'integer', Rule::exists('hotel_rooms', 'id')],
                    'reason' => ['nullable', 'string'],
                ])['to_room_id'],
                $user,
                $request->input('reason'),
            ),
            default => throw new HttpException(404),
        };

        return $this->successResponse('reservations', $reservation->id, 'Reservation workflow updated.');
    }

    protected function performFolioAction(User $user, Folio $folio, string $action, Request $request): array
    {
        match ($action) {
            'add-charge' => $this->authorize($user, 'update-folio'),
            'issue-invoice' => $this->authorize($user, 'create-invoice'),
            default => throw new HttpException(404),
        };

        if ($action === 'add-charge') {
            $validated = $request->validate([
                'description' => ['required', 'string', 'max:255'],
                'line_type' => ['nullable', 'string', 'max:40'],
                'quantity' => ['required', 'numeric', 'min:0.01'],
                'unit_price' => ['required', 'numeric', 'min:0'],
                'tax_amount' => ['nullable', 'numeric', 'min:0'],
                'service_date' => ['nullable', 'date'],
            ]);

            $this->folioService->addCharge($folio, $validated);

            return $this->successResponse('folios', $folio->id, 'Folio charge posted.');
        }

        $validated = $request->validate([
            'issue_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $invoice = $this->accountsReceivableService->issueInvoiceFromFolio($folio, $validated);

        return $this->successResponse('invoices', $invoice->id, 'Invoice issued from folio.');
    }

    protected function performInvoiceAction(User $user, Invoice $invoice, string $action, Request $request): array
    {
        if ($action === 'post-payment') {
            $this->authorize($user, 'create-payment');
            $this->accountsReceivableService->recordPayment($invoice, $request->validate([
                'payment_method' => ['nullable', 'string', 'max:40'],
                'paid_at' => ['nullable', 'date'],
                'amount' => ['required', 'numeric', 'min:0.01'],
                'reference' => ['nullable', 'string', 'max:255'],
            ]));

            return $this->successResponse('invoices', $invoice->id, 'Invoice payment posted.');
        }

        if ($action === 'post-refund') {
            $this->authorize($user, 'create-refund');
            $this->accountsReceivableService->recordRefund($invoice, $request->validate([
                'payment_id' => ['nullable', 'integer', Rule::exists('accounting_payments', 'id')],
                'refunded_at' => ['nullable', 'date'],
                'amount' => ['required', 'numeric', 'min:0.01'],
                'reason' => ['nullable', 'string'],
            ]));

            return $this->successResponse('invoices', $invoice->id, 'Invoice refund posted.');
        }

        throw new HttpException(404);
    }

    protected function performSupplierBillAction(User $user, SupplierBill $bill, string $action, Request $request): array
    {
        if ($action !== 'post-payment') {
            throw new HttpException(404);
        }

        $this->authorize($user, 'create-supplier-payment');
        $this->accountsPayableService->recordSupplierPayment($bill, $request->validate([
            'payment_method' => ['nullable', 'string', 'max:40'],
            'paid_at' => ['nullable', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]));

        return $this->successResponse('supplier-bills', $bill->id, 'Supplier payment posted.');
    }

    protected function performPurchaseOrderAction(User $user, PurchaseOrder $order, string $action, Request $request): array
    {
        $this->authorize($user, 'update-purchase-order');

        match ($action) {
            'approve' => $this->inventoryService->approvePurchaseOrder($order, $user->id, $request->validate(['notes' => ['nullable', 'string']])['notes'] ?? null),
            'reject' => $this->inventoryService->rejectPurchaseOrder($order, $user->id, $request->validate(['notes' => ['nullable', 'string']])['notes'] ?? null),
            'receive' => $this->inventoryService->receivePurchaseOrder($order, $request->validate([
                'received_at' => ['nullable', 'date'],
                'notes' => ['nullable', 'string'],
                'lines' => ['required', 'array', 'min:1'],
                'lines.*.purchase_order_line_id' => ['required', 'integer', Rule::exists('procurement_purchase_order_lines', 'id')],
                'lines.*.received_quantity' => ['required', 'numeric', 'min:0.01'],
                'lines.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            ])),
            default => throw new HttpException(404),
        };

        return $this->successResponse('purchase-orders', $order->id, 'Purchase order updated.');
    }

    protected function performPosOrderAction(User $user, PosOrder $order, string $action, Request $request): array
    {
        $this->authorize($user, 'update-pos-order');

        match ($action) {
            'post-to-folio' => $this->posService->postOrderToFolio($order),
            'send-to-kitchen' => $this->posService->sendToKitchen($order, []),
            'mark-kitchen-ready' => $this->posService->markKitchenReady($order, []),
            'void' => $this->posService->voidOrder($order, $request->validate([
                'reason' => ['required', 'string', 'max:255'],
                'inventory_disposition' => ['nullable', Rule::in(['restock', 'waste'])],
            ])),
            'record-wastage' => $this->posService->recordWastage($order, $request->validate([
                'pos_order_line_id' => ['required', 'integer', Rule::exists('hotel_pos_order_lines', 'id')],
                'wasted_quantity' => ['required', 'numeric', 'min:0.01'],
                'reason' => ['nullable', 'string', 'max:255'],
            ])),
            default => throw new HttpException(404),
        };

        return $this->successResponse('pos-orders', $order->id, 'POS order workflow updated.');
    }

    protected function performCashierShiftAction(User $user, PosCashierShift $shift, string $action, Request $request): array
    {
        if ($action !== 'close') {
            throw new HttpException(404);
        }

        $this->authorize($user, 'update-pos-cashier-shift');
        $this->posService->closeShift($shift, $request->validate([
            'closing_cash_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]));

        return $this->successResponse('pos-cashier-shifts', $shift->id, 'Cashier shift closed.');
    }

    protected function performMaintenanceAction(User $user, MaintenanceRequest $maintenanceRequest, string $action, Request $request): array
    {
        if ($action !== 'update') {
            throw new HttpException(404);
        }

        $this->authorize($user, 'update-maintenance-request');
        $this->maintenanceService->update($maintenanceRequest, $request->validate([
            'assigned_to_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'status' => ['nullable', 'string', 'max:30'],
            'priority' => ['nullable', 'string', 'max:20'],
            'technician_notes' => ['nullable', 'string'],
        ]));

        return $this->successResponse('maintenance-requests', $maintenanceRequest->id, 'Maintenance request updated.');
    }

    protected function performHousekeepingAction(User $user, HousekeepingTask $task, string $action, Request $request): array
    {
        if ($action !== 'update') {
            throw new HttpException(404);
        }

        $this->authorize($user, 'update-housekeeping-task');
        $this->housekeepingService->updateTask($task, $request->validate([
            'assigned_to_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'status' => ['nullable', 'string', 'max:30'],
            'inspection_status' => ['nullable', 'string', 'max:30'],
            'inspection_notes' => ['nullable', 'string'],
            'minibar_status' => ['nullable', 'string', 'max:30'],
        ]));

        return $this->successResponse('housekeeping-tasks', $task->id, 'Housekeeping task updated.');
    }

    protected function performBankReconciliationAction(User $user, BankReconciliation $reconciliation, string $action, Request $request): array
    {
        if ($action !== 'complete') {
            throw new HttpException(404);
        }

        $this->authorize($user, 'update-bank-reconciliation');
        $this->bankReconciliationService->update($reconciliation, [
            'status' => BankReconciliation::STATUS_COMPLETED,
        ]);

        return $this->successResponse('bank-reconciliations', $reconciliation->id, 'Bank reconciliation completed.');
    }

    protected function performPreventiveScheduleAction(User $user, PreventiveMaintenanceSchedule $schedule, string $action): array
    {
        if ($action !== 'generate') {
            throw new HttpException(404);
        }

        $this->authorize($user, 'update-preventive-maintenance-schedule');
        $request = $this->maintenanceService->generateFromSchedule($schedule, $user);

        return $this->successResponse('maintenance-requests', $request->id, 'Maintenance request generated from schedule.');
    }

    protected function successResponse(string $module, int|string $recordId, string $message): array
    {
        return [
            'message' => $message,
            'redirect' => route('admin.workspace.records.show', ['module' => $module, 'record' => $recordId]),
        ];
    }

    protected function authorize(User $user, string $permission): void
    {
        if (! $user->can($permission)) {
            throw new HttpException(403);
        }
    }

    protected function recordTitle(Model $record): string
    {
        foreach (['name', 'title', 'room_number', 'invoice_number', 'bill_number', 'payment_number'] as $field) {
            if (! empty($record->{$field})) {
                return (string) $record->{$field};
            }
        }

        return class_basename($record).' #'.$record->getKey();
    }

    protected function metric(string $label, string $value): array
    {
        return compact('label', 'value');
    }

    protected function detail(string $label, string $value): array
    {
        return compact('label', 'value');
    }

    protected function table(string $title, array $columns, array $rows): array
    {
        return compact('title', 'columns', 'rows');
    }

    protected function actionForm(Model $record, string $module, string $action, string $title, string $description, array $fields, string $submitLabel): array
    {
        return [
            'title' => $title,
            'description' => $description,
            'route' => route('admin.workspace.records.actions.store', ['module' => $module, 'record' => $record->getKey(), 'action' => $action]),
            'fields' => $fields,
            'submit_label' => $submitLabel,
        ];
    }

    protected function field(string $name, string $label, string $type = 'text', mixed $value = null, array $options = [], bool $required = false): array
    {
        return [
            'name' => $name,
            'label' => $label,
            'type' => $type,
            'value' => $value,
            'options' => $options,
            'required' => $required,
        ];
    }

    protected function editableModules(): Collection
    {
        return collect(['properties', 'rooms', 'guests', 'suppliers', 'bank-accounts', 'inventory-items']);
    }
}