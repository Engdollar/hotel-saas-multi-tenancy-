<?php

namespace App\Services;

use App\Domain\Accounting\Models\BankAccount;
use App\Domain\Accounting\Models\BankReconciliation;
use App\Domain\Accounting\Models\Invoice;
use App\Domain\Accounting\Models\LedgerAccount;
use App\Domain\Accounting\Models\Payment;
use App\Domain\Accounting\Models\Refund;
use App\Domain\Accounting\Models\Supplier;
use App\Domain\Accounting\Models\SupplierBill;
use App\Domain\Accounting\Models\SupplierPayment;
use App\Domain\Accounting\Services\AccountsPayableService;
use App\Domain\Accounting\Services\FinanceReportingService;
use App\Domain\Hotel\Models\Folio;
use App\Domain\Hotel\Models\GuestProfile;
use App\Domain\Hotel\Models\HousekeepingTask;
use App\Domain\Hotel\Models\MaintenanceRequest;
use App\Domain\Hotel\Models\PosCashierShift;
use App\Domain\Hotel\Models\PosOrder;
use App\Domain\Hotel\Models\PreventiveMaintenanceSchedule;
use App\Domain\Hotel\Models\Property;
use App\Domain\Hotel\Models\Reservation;
use App\Domain\Hotel\Models\Room;
use App\Domain\Hotel\Models\RoomType;
use App\Domain\Hotel\Services\FolioService;
use App\Domain\Hotel\Services\HousekeepingService;
use App\Domain\Hotel\Services\MaintenanceService;
use App\Domain\Hotel\Services\PosService;
use App\Domain\Hotel\Services\ReservationService;
use App\Domain\Accounting\Services\AccountsReceivableService;
use App\Domain\Accounting\Services\BankReconciliationService;
use App\Domain\Inventory\Models\InventoryItem;
use App\Domain\Inventory\Models\InventoryMovement;
use App\Domain\Inventory\Models\PurchaseOrder;
use App\Domain\Inventory\Models\PurchaseOrderApproval;
use App\Domain\Inventory\Services\InventoryService;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TenantWorkspaceService
{
    public function __construct(
        protected FinanceReportingService $financeReportingService,
        protected ReservationService $reservationService,
        protected FolioService $folioService,
        protected AccountsReceivableService $accountsReceivableService,
        protected MaintenanceService $maintenanceService,
        protected HousekeepingService $housekeepingService,
        protected AccountsPayableService $accountsPayableService,
        protected BankReconciliationService $bankReconciliationService,
        protected InventoryService $inventoryService,
        protected PosService $posService,
    ) {
    }

    public function isTenantWorkspaceUser(?User $user): bool
    {
        return $user !== null && ! $user->isSuperAdmin() && $user->company_id !== null;
    }

    public function workspaceLabel(?User $user): string
    {
        return $this->isTenantWorkspaceUser($user) ? 'Tenant Workspace' : 'Admin Panel';
    }

    public function navigation(User $user): array
    {
        if (! $this->isTenantWorkspaceUser($user)) {
            return [];
        }

        $counts = $this->badgeCounts();
        $modules = collect($this->moduleCatalog($user))->keyBy('key');

        $sections = [
            [
                'label' => 'Front Desk',
                'items' => ['dashboard', 'properties', 'rooms', 'guests', 'reservations', 'folios'],
            ],
            [
                'label' => 'Finance',
                'items' => ['invoices', 'payments', 'refunds', 'supplier-bills', 'supplier-payments', 'finance-ar-aging', 'finance-ap-aging', 'bank-accounts', 'bank-reconciliations'],
            ],
            [
                'label' => 'Operations',
                'items' => ['housekeeping-tasks', 'maintenance-requests', 'preventive-maintenance-schedules'],
            ],
            [
                'label' => 'POS & Inventory',
                'items' => ['pos-cashier-shifts', 'pos-orders', 'inventory-items', 'inventory-movements', 'purchase-orders'],
            ],
            [
                'label' => 'Administration',
                'items' => ['company-profile', 'tickets'],
            ],
        ];

        return collect($sections)
            ->map(function (array $section) use ($modules, $counts) {
                $items = collect($section['items'])
                    ->map(function (string $key) use ($modules, $counts) {
                        $module = $modules->get($key);

                        if (! $module) {
                            return null;
                        }

                        $badgeKey = $module['badge_key'] ?? null;

                        return [
                            ...$module,
                            'badge' => $badgeKey ? ($counts[$badgeKey] ?? null) : null,
                        ];
                    })
                    ->filter()
                    ->values()
                    ->all();

                return $items === [] ? null : [
                    'label' => $section['label'],
                    'items' => $items,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public function buildDashboard(User $user): array
    {
        $today = now()->toDateString();
        $arrivalStatuses = [
            Reservation::STATUS_PENDING,
            Reservation::STATUS_CONFIRMED,
            Reservation::STATUS_CHECKED_IN,
        ];
        $departureStatuses = [
            Reservation::STATUS_CONFIRMED,
            Reservation::STATUS_CHECKED_IN,
        ];
        $openHousekeepingStatuses = [HousekeepingTask::STATUS_PENDING, HousekeepingTask::STATUS_IN_PROGRESS];
        $openMaintenanceStatuses = [MaintenanceRequest::STATUS_OPEN, MaintenanceRequest::STATUS_IN_PROGRESS];
        $openSupplierBillStatuses = [SupplierBill::STATUS_DRAFT, SupplierBill::STATUS_APPROVED, SupplierBill::STATUS_PARTIALLY_PAID];
        $arSummary = $this->financeReportingService->arAgingSummary($today);
        $apSummary = $this->financeReportingService->apAgingSummary($today);

        $stats = [
            $this->statCard('Occupied rooms', Room::query()->where('status', Room::STATUS_OCCUPIED)->count(), 'Rooms currently checked in.', 'layers'),
            $this->statCard('Available rooms', Room::query()->where('status', Room::STATUS_AVAILABLE)->count(), 'Ready inventory for new arrivals.', 'sparkles'),
            $this->statCard('Arrivals today', Reservation::query()->whereDate('check_in_date', $today)->whereIn('status', $arrivalStatuses)->count(), 'Expected and checked-in arrivals.', 'arrow-right'),
            $this->statCard('Departures today', Reservation::query()->whereDate('check_out_date', $today)->whereIn('status', $departureStatuses)->count(), 'Stays scheduled to depart today.', 'activity'),
            $this->moneyStatCard('Open folio balance', (float) Folio::query()->where('balance_amount', '>', 0)->sum('balance_amount'), 'Open guest-ledger exposure.', 'database'),
            $this->moneyStatCard('Outstanding AR', (float) Invoice::query()->where('balance_amount', '>', 0)->where('status', '!=', Invoice::STATUS_VOID)->sum('balance_amount'), 'Issued invoices still unpaid.', 'chart-bar'),
            $this->moneyStatCard('Outstanding AP', (float) SupplierBill::query()->where('balance_amount', '>', 0)->where('status', '!=', SupplierBill::STATUS_VOID)->sum('balance_amount'), 'Approved supplier liabilities.', 'filter'),
            $this->moneyStatCard('POS sales today', (float) PosOrder::query()->where('status', PosOrder::STATUS_PAID)->whereDate('paid_at', $today)->sum('total_amount'), 'Paid POS revenue posted today.', 'check-square'),
        ];

        $definitions = [
            'stats' => ['label' => 'Stats'],
            'operations' => ['label' => 'Operations'],
            'finance' => ['label' => 'Finance'],
            'procurement' => ['label' => 'Procurement'],
            'pos' => ['label' => 'POS'],
            'quickActions' => ['label' => 'Quick actions'],
        ];

        return [
            'isTenantWorkspace' => true,
            'pageTitle' => 'Tenant ERP Dashboard',
            'pageDescription' => 'Hotel operations, finance exposure, procurement backlog, and POS performance for the active company.',
            'stats' => $stats,
            'quickActions' => $this->quickActions($user),
            'tenantSections' => [
                'operations' => [
                    'title' => 'Front desk and operations',
                    'description' => 'What the property team needs to act on next.',
                    'cards' => [
                        [
                            'label' => 'In-house guests',
                            'value' => Reservation::query()->where('status', Reservation::STATUS_CHECKED_IN)->count(),
                            'description' => 'Reservations currently checked in.',
                        ],
                        [
                            'label' => 'Open housekeeping',
                            'value' => HousekeepingTask::query()->whereIn('status', $openHousekeepingStatuses)->count(),
                            'description' => 'Tasks still pending or in progress.',
                        ],
                        [
                            'label' => 'Open maintenance',
                            'value' => MaintenanceRequest::query()->whereIn('status', $openMaintenanceStatuses)->count(),
                            'description' => 'Issues waiting for engineering follow-up.',
                        ],
                    ],
                    'lists' => [
                        [
                            'title' => 'Arrivals today',
                            'items' => Reservation::query()
                                ->with(['guestProfile', 'room'])
                                ->whereDate('check_in_date', $today)
                                ->whereIn('status', $arrivalStatuses)
                                ->orderBy('check_in_date')
                                ->take(5)
                                ->get()
                                ->map(fn (Reservation $reservation) => [
                                    'title' => $reservation->guestProfile?->first_name.' '.$reservation->guestProfile?->last_name,
                                    'meta' => trim(($reservation->room?->room_number ? 'Room '.$reservation->room->room_number : 'Room pending').' · '.str($reservation->status)->headline()),
                                ])
                                ->all(),
                        ],
                        [
                            'title' => 'Departures today',
                            'items' => Reservation::query()
                                ->with(['guestProfile', 'room'])
                                ->whereDate('check_out_date', $today)
                                ->whereIn('status', $departureStatuses)
                                ->orderBy('check_out_date')
                                ->take(5)
                                ->get()
                                ->map(fn (Reservation $reservation) => [
                                    'title' => $reservation->guestProfile?->first_name.' '.$reservation->guestProfile?->last_name,
                                    'meta' => trim(($reservation->room?->room_number ? 'Room '.$reservation->room->room_number : 'Room pending').' · '.str($reservation->status)->headline()),
                                ])
                                ->all(),
                        ],
                    ],
                ],
                'finance' => [
                    'title' => 'Finance snapshot',
                    'description' => 'Live receivables and payables exposure.',
                    'cards' => [
                        [
                            'label' => 'AR open invoices',
                            'value' => $arSummary['open_count'],
                            'description' => 'Guest or corporate receivables still open.',
                        ],
                        [
                            'label' => 'AR 31+ days',
                            'value' => number_format((float) $arSummary['buckets']['31_60'] + (float) $arSummary['buckets']['61_90'] + (float) $arSummary['buckets']['91_plus'], 2, '.', ''),
                            'description' => 'Aging receivables that need action.',
                        ],
                        [
                            'label' => 'AP open bills',
                            'value' => $apSummary['open_count'],
                            'description' => 'Supplier bills awaiting settlement.',
                        ],
                    ],
                    'lists' => [
                        [
                            'title' => 'Receivables aging',
                            'items' => $this->agingListItems($arSummary),
                        ],
                        [
                            'title' => 'Payables aging',
                            'items' => $this->agingListItems($apSummary),
                        ],
                    ],
                ],
                'procurement' => [
                    'title' => 'Procurement and stock',
                    'description' => 'Supply chain alerts that block service delivery.',
                    'cards' => [
                        [
                            'label' => 'Low stock items',
                            'value' => InventoryItem::query()->where('reorder_level', '>', 0)->whereColumn('current_quantity', '<=', 'reorder_level')->count(),
                            'description' => 'Items at or below reorder level.',
                        ],
                        [
                            'label' => 'Pending approvals',
                            'value' => PurchaseOrderApproval::query()->where('status', PurchaseOrderApproval::STATUS_PENDING)->count(),
                            'description' => 'Approval chain steps waiting on approvers.',
                        ],
                        [
                            'label' => 'Bill exceptions',
                            'value' => SupplierBill::query()->where('match_status', SupplierBill::MATCH_STATUS_EXCEPTION)->whereIn('status', $openSupplierBillStatuses)->count(),
                            'description' => 'Three-way match exceptions still unresolved.',
                        ],
                    ],
                    'lists' => [
                        [
                            'title' => 'Low stock watchlist',
                            'items' => InventoryItem::query()
                                ->where('reorder_level', '>', 0)
                                ->whereColumn('current_quantity', '<=', 'reorder_level')
                                ->orderBy('current_quantity')
                                ->take(5)
                                ->get()
                                ->map(fn (InventoryItem $item) => [
                                    'title' => $item->name,
                                    'meta' => 'Qty '.number_format((float) $item->current_quantity, 2).' · Reorder '.number_format((float) $item->reorder_level, 2),
                                ])
                                ->all(),
                        ],
                        [
                            'title' => 'Purchase orders awaiting approval',
                            'items' => PurchaseOrder::query()
                                ->with(['supplier'])
                                ->where('status', PurchaseOrder::STATUS_DRAFT)
                                ->latest('order_date')
                                ->take(5)
                                ->get()
                                ->map(fn (PurchaseOrder $order) => [
                                    'title' => $order->purchase_order_number,
                                    'meta' => trim(($order->supplier?->name ?? 'Supplier pending').' · '.number_format((float) $order->total_amount, 2).' '.$order->currency_code),
                                ])
                                ->all(),
                        ],
                    ],
                ],
                'pos' => [
                    'title' => 'POS and service desks',
                    'description' => 'Paid order flow and cashier activity.',
                    'cards' => [
                        [
                            'label' => 'Paid POS orders',
                            'value' => PosOrder::query()->whereDate('paid_at', $today)->where('status', PosOrder::STATUS_PAID)->count(),
                            'description' => 'Completed orders for the current day.',
                        ],
                        [
                            'label' => 'Open cashier shifts',
                            'value' => PosCashierShift::query()->where('status', PosCashierShift::STATUS_OPEN)->count(),
                            'description' => 'Registers still open on property.',
                        ],
                        [
                            'label' => 'Void orders today',
                            'value' => PosOrder::query()->where('status', PosOrder::STATUS_VOID)->whereDate('voided_at', $today)->count(),
                            'description' => 'Orders voided or reversed today.',
                        ],
                    ],
                    'lists' => [
                        [
                            'title' => 'Recent paid orders',
                            'items' => PosOrder::query()
                                ->with('property')
                                ->where('status', PosOrder::STATUS_PAID)
                                ->latest('paid_at')
                                ->take(5)
                                ->get()
                                ->map(fn (PosOrder $order) => [
                                    'title' => $order->order_number,
                                    'meta' => trim(($order->property?->name ?? 'Property pending').' · '.number_format((float) $order->total_amount, 2).' · '.str($order->payment_method ?? 'unpaid')->headline()),
                                ])
                                ->all(),
                        ],
                    ],
                ],
            ],
            'widgetDefinitions' => $definitions,
            'widgetState' => $this->widgetState($user, $definitions),
            'widgetLayout' => $this->widgetLayout($user, $definitions),
            'dragEnabled' => (bool) ($user->dashboardPreference?->drag_enabled ?? true),
            'canDragWidgets' => true,
            'chartWidgets' => [],
            'recentActivities' => collect(),
            'subjectLabels' => [],
            'intelligencePreview' => null,
        ];
    }

    public function quickActions(User $user): array
    {
        $actions = [
            [
                'title' => 'Create reservation',
                'description' => 'Open a new guest stay from the tenant workspace.',
                'icon' => 'plus',
                'url' => route('admin.workspace.modules.create', ['module' => 'reservations']),
                'permission' => 'create-reservation',
            ],
            [
                'title' => 'Open folio',
                'description' => 'Create or reopen a folio for an active reservation.',
                'icon' => 'database',
                'url' => route('admin.workspace.modules.create', ['module' => 'folios']),
                'permission' => 'create-folio',
            ],
            [
                'title' => 'Create supplier bill',
                'description' => 'Capture a supplier invoice against live AP.',
                'icon' => 'filter',
                'url' => route('admin.workspace.modules.create', ['module' => 'supplier-bills']),
                'permission' => 'create-supplier-bill',
            ],
            [
                'title' => 'Open maintenance request',
                'description' => 'Log an engineering issue for follow-up.',
                'icon' => 'activity',
                'url' => route('admin.workspace.modules.create', ['module' => 'maintenance-requests']),
                'permission' => 'create-maintenance-request',
            ],
            [
                'title' => 'Create purchase order',
                'description' => 'Start a procurement request with supplier and item linkage.',
                'icon' => 'layers',
                'url' => route('admin.workspace.modules.create', ['module' => 'purchase-orders']),
                'permission' => 'create-purchase-order',
            ],
            [
                'title' => 'Open POS order',
                'description' => 'Create an inventory-backed POS order from the workspace.',
                'icon' => 'check-square',
                'url' => route('admin.workspace.modules.create', ['module' => 'pos-orders']),
                'permission' => 'create-pos-order',
            ],
        ];

        return collect($actions)
            ->filter(fn (array $action) => $user->can($action['permission']))
            ->values()
            ->all();
    }

    public function moduleCatalog(User $user): array
    {
        $modules = [
            [
                'key' => 'dashboard',
                'label' => 'Dashboard',
                'description' => 'Tenant ERP operational dashboard.',
                'icon' => 'sparkles',
                'permission' => 'read-dashboard',
                'route' => route('admin.dashboard'),
            ],
            $this->moduleEntry('properties', 'Properties', 'Property portfolio, branch codes, and operating status.', 'layers', 'read-property', 'admin.workspace.modules.show', 'properties', 'admin.workspace.modules.create', 'create-property'),
            $this->moduleEntry('rooms', 'Rooms', 'Room inventory, housekeeping state, and availability.', 'database', 'read-room', 'admin.workspace.modules.show', 'rooms', 'admin.workspace.modules.create', 'create-room'),
            $this->moduleEntry('guests', 'Guests', 'Guest profiles, contact details, and VIP flags.', 'user', 'read-guest', 'admin.workspace.modules.show', 'guests', 'admin.workspace.modules.create', 'create-guest'),
            $this->moduleEntry('reservations', 'Reservations', 'Stay pipeline across pending, confirmed, and in-house guests.', 'arrow-right', 'read-reservation', 'admin.workspace.modules.show', 'reservations', 'admin.workspace.modules.create', 'create-reservation'),
            $this->moduleEntry('folios', 'Folios', 'Open guest ledgers and outstanding balances.', 'database', 'read-folio', 'admin.workspace.modules.show', 'folios', 'admin.workspace.modules.create', 'create-folio'),
            $this->moduleEntry('invoices', 'Invoices', 'Issued receivables and balance tracking.', 'chart-bar', 'read-invoice', 'admin.workspace.modules.show', 'invoices', 'admin.workspace.modules.create', 'create-invoice'),
            $this->moduleEntry('payments', 'Payments', 'Posted guest payments applied to invoices.', 'check-square', 'read-payment', 'admin.workspace.modules.show', 'payments'),
            $this->moduleEntry('refunds', 'Refunds', 'Refunded receivables and adjustments.', 'activity', 'read-refund', 'admin.workspace.modules.show', 'refunds'),
            $this->moduleEntry('supplier-bills', 'Supplier Bills', 'Supplier AP, PO matching, and exceptions.', 'filter', 'read-supplier-bill', 'admin.workspace.modules.show', 'supplier-bills', 'admin.workspace.modules.create', 'create-supplier-bill', 'supplier_bill_exceptions'),
            $this->moduleEntry('supplier-payments', 'Supplier Payments', 'Disbursements applied to supplier liabilities.', 'check-square', 'read-supplier-payment', 'admin.workspace.modules.show', 'supplier-payments'),
            $this->moduleEntry('finance-ar-aging', 'AR Aging', 'Receivables aging by due bucket.', 'chart-bar', 'read-report', 'admin.workspace.modules.show', 'finance-ar-aging'),
            $this->moduleEntry('finance-ap-aging', 'AP Aging', 'Payables aging by due bucket.', 'filter', 'read-report', 'admin.workspace.modules.show', 'finance-ap-aging'),
            $this->moduleEntry('bank-accounts', 'Bank Accounts', 'Operating bank balances and activity.', 'database', 'read-bank-account', 'admin.workspace.modules.show', 'bank-accounts', 'admin.workspace.modules.create', 'create-bank-account'),
            $this->moduleEntry('bank-reconciliations', 'Bank Reconciliation', 'Statement matching and close status.', 'layers', 'read-bank-reconciliation', 'admin.workspace.modules.show', 'bank-reconciliations', 'admin.workspace.modules.create', 'create-bank-reconciliation'),
            $this->moduleEntry('housekeeping-tasks', 'Housekeeping Tasks', 'Room cleaning workload and inspections.', 'activity', 'read-housekeeping-task', 'admin.workspace.modules.show', 'housekeeping-tasks', 'admin.workspace.modules.create', 'create-housekeeping-task', 'open_housekeeping', 'update-housekeeping-task'),
            $this->moduleEntry('maintenance-requests', 'Maintenance Requests', 'Open engineering issues and room downtime.', 'activity', 'read-maintenance-request', 'admin.workspace.modules.show', 'maintenance-requests', 'admin.workspace.modules.create', 'create-maintenance-request', 'open_maintenance', 'update-maintenance-request'),
            $this->moduleEntry('preventive-maintenance-schedules', 'Preventive Maintenance', 'Recurring engineering schedules.', 'settings', 'read-preventive-maintenance-schedule', 'admin.workspace.modules.show', 'preventive-maintenance-schedules', 'admin.workspace.modules.create', 'create-preventive-maintenance-schedule', null, 'update-preventive-maintenance-schedule'),
            $this->moduleEntry('pos-cashier-shifts', 'Cashier Shifts', 'Open and closed cashier shifts.', 'users', 'read-pos-cashier-shift', 'admin.workspace.modules.show', 'pos-cashier-shifts', 'admin.workspace.modules.create', 'create-pos-cashier-shift'),
            $this->moduleEntry('pos-orders', 'POS Orders', 'POS activity with inventory linkage.', 'check-square', 'read-pos-order', 'admin.workspace.modules.show', 'pos-orders', 'admin.workspace.modules.create', 'create-pos-order'),
            $this->moduleEntry('inventory-items', 'Inventory Items', 'Shared stock catalog, reorder points, and on-hand quantities.', 'database', 'read-inventory-item', 'admin.workspace.modules.show', 'inventory-items', 'admin.workspace.modules.create', 'create-inventory-item', 'low_stock_items'),
            $this->moduleEntry('inventory-movements', 'Stock Movements', 'Inventory receipts, issues, adjustments, and wastage history.', 'activity', 'read-inventory-item', 'admin.workspace.modules.show', 'inventory-movements'),
            $this->moduleEntry('purchase-orders', 'Purchase Orders', 'Procurement approvals, receiving, and bill matching.', 'layers', 'read-purchase-order', 'admin.workspace.modules.show', 'purchase-orders', 'admin.workspace.modules.create', 'create-purchase-order', 'pending_approvals'),
            $this->moduleEntry('suppliers', 'Suppliers', 'Vendor master, contact details, and active purchasing partners.', 'users', 'read-supplier', 'admin.workspace.modules.show', 'suppliers', 'admin.workspace.modules.create', 'create-supplier'),
            [
                'key' => 'company-profile',
                'label' => 'Company Profile',
                'description' => 'Tenant-level operating identity and company details.',
                'icon' => 'layers',
                'permission' => 'read-setting',
                'route' => route('admin.company-profile.edit'),
            ],
            [
                'key' => 'tickets',
                'label' => 'Support Tickets',
                'description' => 'Support issues and platform escalation threads.',
                'icon' => 'activity',
                'permission' => 'read-ticket',
                'route' => route('admin.tickets.index'),
                'badge_key' => 'open_tickets',
            ],
        ];

        return collect($modules)
            ->filter(fn (array $module) => ! isset($module['permission']) || $user->can($module['permission']))
            ->values()
            ->all();
    }

    public function modulePage(User $user, string $module, ?Request $request = null): array
    {
        $entry = $this->findModule($user, $module);
        $request ??= request();

        return match ($module) {
            'properties' => $this->tablePage($entry, Property::query()->latest('id'), $request, [
                ['label' => 'Branch', 'value' => fn (Property $property) => $property->branch_code],
                ['label' => 'Property', 'value' => fn (Property $property) => $property->name],
                ['label' => 'Type', 'value' => fn (Property $property) => str($property->property_type)->headline()],
                ['label' => 'Status', 'value' => fn (Property $property) => str($property->status)->headline()],
            ], [
                $this->summaryMetric('Active properties', Property::query()->where('status', 'active')->count(), 'Live branches in operation.'),
                $this->summaryMetric('Room inventory', Room::query()->count(), 'Rooms attached to current properties.'),
            ], $this->tableOptions(
                searchPlaceholder: 'Search branch code or property name',
                searchCallback: fn (Builder $query, string $term) => $query->where(fn (Builder $builder) => $builder
                    ->where('branch_code', 'like', '%'.$term.'%')
                    ->orWhere('name', 'like', '%'.$term.'%')),
                filters: [
                    $this->filterDefinition('status', 'Status', $this->optionsFromValues(['active', 'inactive', 'pending']), fn (Builder $query, string $value) => $query->where('status', $value)),
                ],
            )),
            'rooms' => $this->tablePage($entry, Room::query()->with('property')->latest('id'), $request, [
                ['label' => 'Room', 'value' => fn (Room $room) => $room->room_number],
                ['label' => 'Property', 'value' => fn (Room $room) => $room->property?->name],
                ['label' => 'Status', 'value' => fn (Room $room) => str($room->status)->headline()],
                ['label' => 'Cleaning', 'value' => fn (Room $room) => str($room->cleaning_status)->headline()],
            ], [
                $this->summaryMetric('Available', Room::query()->where('status', Room::STATUS_AVAILABLE)->count(), 'Rooms ready to sell.'),
                $this->summaryMetric('Occupied', Room::query()->where('status', Room::STATUS_OCCUPIED)->count(), 'Rooms currently in-house.'),
            ], $this->tableOptions(
                searchPlaceholder: 'Search room number or property',
                searchCallback: fn (Builder $query, string $term) => $query->where(fn (Builder $builder) => $builder
                    ->where('room_number', 'like', '%'.$term.'%')
                    ->orWhereHas('property', fn (Builder $propertyQuery) => $propertyQuery->where('name', 'like', '%'.$term.'%'))),
                filters: [
                    $this->filterDefinition('status', 'Status', $this->optionsFromValues(Room::statuses()), fn (Builder $query, string $value) => $query->where('status', $value)),
                    $this->filterDefinition('cleaning_status', 'Cleaning', $this->optionsFromValues(['clean', 'dirty', 'inspected']), fn (Builder $query, string $value) => $query->where('cleaning_status', $value)),
                ],
            )),
            'guests' => $this->tablePage($entry, GuestProfile::query()->latest('id'), $request, [
                ['label' => 'Guest', 'value' => fn (GuestProfile $guest) => trim($guest->first_name.' '.$guest->last_name)],
                ['label' => 'Email', 'value' => fn (GuestProfile $guest) => $guest->email ?: 'Not provided'],
                ['label' => 'Phone', 'value' => fn (GuestProfile $guest) => $guest->phone ?: 'Not provided'],
                ['label' => 'VIP', 'value' => fn (GuestProfile $guest) => $guest->is_vip ? 'Yes' : 'No'],
            ], [
                $this->summaryMetric('Total guests', GuestProfile::query()->count(), 'Profiles stored for the tenant.'),
                $this->summaryMetric('VIP profiles', GuestProfile::query()->where('is_vip', true)->count(), 'Guests flagged for special handling.'),
            ], $this->tableOptions(
                searchPlaceholder: 'Search guest, email, or phone',
                searchCallback: fn (Builder $query, string $term) => $query->where(fn (Builder $builder) => $builder
                    ->where('first_name', 'like', '%'.$term.'%')
                    ->orWhere('last_name', 'like', '%'.$term.'%')
                    ->orWhere('email', 'like', '%'.$term.'%')
                    ->orWhere('phone', 'like', '%'.$term.'%')),
                filters: [
                    $this->filterDefinition('is_vip', 'VIP', [['value' => '1', 'label' => 'Yes'], ['value' => '0', 'label' => 'No']], fn (Builder $query, string $value) => $query->where('is_vip', $value === '1')),
                ],
            )),
            'reservations' => $this->tablePage($entry, Reservation::query()->with(['guestProfile', 'room'])->latest('check_in_date'), $request, [
                ['label' => 'Reservation', 'value' => fn (Reservation $reservation) => $reservation->reservation_number],
                ['label' => 'Guest', 'value' => fn (Reservation $reservation) => trim(($reservation->guestProfile?->first_name ?? '').' '.($reservation->guestProfile?->last_name ?? ''))],
                ['label' => 'Room', 'value' => fn (Reservation $reservation) => $reservation->room?->room_number ?: 'Unassigned'],
                ['label' => 'Status', 'value' => fn (Reservation $reservation) => str($reservation->status)->headline()],
                ['label' => 'Stay', 'value' => fn (Reservation $reservation) => $reservation->check_in_date?->toDateString().' to '.$reservation->check_out_date?->toDateString()],
            ], [
                $this->summaryMetric('Arrivals today', Reservation::query()->whereDate('check_in_date', now()->toDateString())->count(), 'Reservations arriving today.'),
                $this->summaryMetric('In house', Reservation::query()->where('status', Reservation::STATUS_CHECKED_IN)->count(), 'Guests already checked in.'),
            ], $this->tableOptions(
                searchPlaceholder: 'Search reservation or folio number',
                searchCallback: fn (Builder $query, string $term) => $query->where(fn (Builder $builder) => $builder
                    ->where('reservation_number', 'like', '%'.$term.'%')
                    ->orWhereHas('guestProfile', fn (Builder $guestQuery) => $guestQuery->whereRaw("concat(first_name, ' ', last_name) like ?", ['%'.$term.'%']))),
                filters: [
                    $this->filterDefinition('status', 'Status', $this->optionsFromValues([Reservation::STATUS_PENDING, Reservation::STATUS_CONFIRMED, Reservation::STATUS_CHECKED_IN, Reservation::STATUS_CHECKED_OUT, Reservation::STATUS_CANCELLED]), fn (Builder $query, string $value) => $query->where('status', $value)),
                ],
            )),
            'folios' => $this->tablePage($entry, Folio::query()->with('reservation')->latest('id'), $request, [
                ['label' => 'Folio', 'value' => fn (Folio $folio) => $folio->folio_number],
                ['label' => 'Reservation', 'value' => fn (Folio $folio) => $folio->reservation?->reservation_number ?: 'Not linked'],
                ['label' => 'Status', 'value' => fn (Folio $folio) => str($folio->status)->headline()],
                ['label' => 'Balance', 'value' => fn (Folio $folio) => number_format((float) $folio->balance_amount, 2)],
            ], [
                $this->summaryMetric('Open folios', Folio::query()->where('status', Folio::STATUS_OPEN)->count(), 'Folios still active.'),
                $this->summaryMetric('Open balance', number_format((float) Folio::query()->where('balance_amount', '>', 0)->sum('balance_amount'), 2), 'Outstanding guest ledger value.'),
            ], $this->tableOptions(
                searchPlaceholder: 'Search folio number',
                searchCallback: fn (Builder $query, string $term) => $query->where('folio_number', 'like', '%'.$term.'%'),
                filters: [
                    $this->filterDefinition('status', 'Status', $this->optionsFromValues([Folio::STATUS_OPEN, Folio::STATUS_INVOICED, Folio::STATUS_CLOSED]), fn (Builder $query, string $value) => $query->where('status', $value)),
                ],
            )),
            'invoices' => $this->tablePage($entry, Invoice::query()->with('guestProfile')->latest('issue_date'), $request, [
                ['label' => 'Invoice', 'value' => fn (Invoice $invoice) => $invoice->invoice_number],
                ['label' => 'Guest', 'value' => fn (Invoice $invoice) => trim(($invoice->guestProfile?->first_name ?? '').' '.($invoice->guestProfile?->last_name ?? '')) ?: 'Walk-in / account'],
                ['label' => 'Status', 'value' => fn (Invoice $invoice) => str($invoice->status)->headline()],
                ['label' => 'Due', 'value' => fn (Invoice $invoice) => $invoice->due_date?->toDateString() ?: 'Not set'],
                ['label' => 'Balance', 'value' => fn (Invoice $invoice) => number_format((float) $invoice->balance_amount, 2)],
            ], [
                $this->summaryMetric('Open invoices', Invoice::query()->where('balance_amount', '>', 0)->count(), 'Receivables that still need payment.'),
                $this->summaryMetric('Outstanding AR', number_format((float) Invoice::query()->where('balance_amount', '>', 0)->sum('balance_amount'), 2), 'Open receivable value.'),
            ], $this->tableOptions(
                searchPlaceholder: 'Search invoice number or guest',
                searchCallback: fn (Builder $query, string $term) => $query->where(fn (Builder $builder) => $builder
                    ->where('invoice_number', 'like', '%'.$term.'%')
                    ->orWhereHas('guestProfile', fn (Builder $guestQuery) => $guestQuery
                        ->where('first_name', 'like', '%'.$term.'%')
                        ->orWhere('last_name', 'like', '%'.$term.'%'))),
                filters: [
                    $this->filterDefinition('status', 'Status', $this->optionsFromValues([Invoice::STATUS_ISSUED, Invoice::STATUS_PARTIALLY_PAID, Invoice::STATUS_PAID, Invoice::STATUS_REFUNDED, Invoice::STATUS_VOID]), fn (Builder $query, string $value) => $query->where('status', $value)),
                ],
            )),
            'payments' => $this->tablePage($entry, Payment::query()->with('invoice')->latest('paid_at'), $request, [
                ['label' => 'Payment', 'value' => fn (Payment $payment) => $payment->payment_number],
                ['label' => 'Invoice', 'value' => fn (Payment $payment) => $payment->invoice?->invoice_number ?: 'Not linked'],
                ['label' => 'Method', 'value' => fn (Payment $payment) => str($payment->payment_method)->headline()],
                ['label' => 'Amount', 'value' => fn (Payment $payment) => number_format((float) $payment->amount, 2)],
                ['label' => 'Paid at', 'value' => fn (Payment $payment) => $payment->paid_at?->format('Y-m-d H:i') ?: 'Pending'],
            ], [
                $this->summaryMetric('Payments today', Payment::query()->whereDate('paid_at', now()->toDateString())->count(), 'Guest payments posted today.'),
                $this->summaryMetric('Cash applied today', number_format((float) Payment::query()->whereDate('paid_at', now()->toDateString())->sum('amount'), 2), 'Amount collected today.'),
            ], $this->tableOptions(
                searchPlaceholder: 'Search payment, reference, or invoice',
                searchCallback: fn (Builder $query, string $term) => $query->where(fn (Builder $builder) => $builder
                    ->where('payment_number', 'like', '%'.$term.'%')
                    ->orWhere('reference', 'like', '%'.$term.'%')
                    ->orWhereHas('invoice', fn (Builder $invoiceQuery) => $invoiceQuery->where('invoice_number', 'like', '%'.$term.'%'))),
                filters: [
                    $this->filterDefinition('payment_method', 'Method', $this->optionsFromValues(['cash', 'card', 'bank_transfer', 'room_charge']), fn (Builder $query, string $value) => $query->where('payment_method', $value)),
                    $this->filterDefinition('status', 'Status', $this->optionsFromValues([Payment::STATUS_POSTED, Payment::STATUS_VOID]), fn (Builder $query, string $value) => $query->where('status', $value)),
                ],
            )),
            'refunds' => $this->tablePage($entry, Refund::query()->with('invoice')->latest('refunded_at'), $request, [
                ['label' => 'Refund', 'value' => fn (Refund $refund) => $refund->refund_number],
                ['label' => 'Invoice', 'value' => fn (Refund $refund) => $refund->invoice?->invoice_number ?: 'Not linked'],
                ['label' => 'Reason', 'value' => fn (Refund $refund) => $refund->reason ?: 'Not provided'],
                ['label' => 'Amount', 'value' => fn (Refund $refund) => number_format((float) $refund->amount, 2)],
                ['label' => 'Refunded at', 'value' => fn (Refund $refund) => $refund->refunded_at?->format('Y-m-d H:i') ?: 'Pending'],
            ], [
                $this->summaryMetric('Refunds today', Refund::query()->whereDate('refunded_at', now()->toDateString())->count(), 'Refund adjustments posted today.'),
                $this->summaryMetric('Refunded value', number_format((float) Refund::query()->sum('amount'), 2), 'Cumulative refund amount.'),
            ], $this->tableOptions(
                searchPlaceholder: 'Search refund, reason, or invoice',
                searchCallback: fn (Builder $query, string $term) => $query->where(fn (Builder $builder) => $builder
                    ->where('refund_number', 'like', '%'.$term.'%')
                    ->orWhere('reason', 'like', '%'.$term.'%')
                    ->orWhereHas('invoice', fn (Builder $invoiceQuery) => $invoiceQuery->where('invoice_number', 'like', '%'.$term.'%'))),
                filters: [
                    $this->filterDefinition('status', 'Status', $this->optionsFromValues([Refund::STATUS_POSTED, Refund::STATUS_VOID]), fn (Builder $query, string $value) => $query->where('status', $value)),
                ],
            )),
            'supplier-bills' => $this->tablePage($entry, SupplierBill::query()->with('supplier')->latest('bill_date'), $request, [
                ['label' => 'Bill', 'value' => fn (SupplierBill $bill) => $bill->bill_number],
                ['label' => 'Supplier', 'value' => fn (SupplierBill $bill) => $bill->supplier?->name ?: 'Not linked'],
                ['label' => 'Status', 'value' => fn (SupplierBill $bill) => str($bill->status)->headline()],
                ['label' => 'Match', 'value' => fn (SupplierBill $bill) => str($bill->match_status)->headline()],
                ['label' => 'Balance', 'value' => fn (SupplierBill $bill) => number_format((float) $bill->balance_amount, 2)],
            ], [
                $this->summaryMetric('Open AP bills', SupplierBill::query()->where('balance_amount', '>', 0)->count(), 'Supplier bills with remaining balance.'),
                $this->summaryMetric('Bill exceptions', SupplierBill::query()->where('match_status', SupplierBill::MATCH_STATUS_EXCEPTION)->count(), 'Bills outside tolerance or unmatched.'),
            ], $this->tableOptions(
                searchPlaceholder: 'Search bill number or supplier',
                searchCallback: fn (Builder $query, string $term) => $query->where(fn (Builder $builder) => $builder
                    ->where('bill_number', 'like', '%'.$term.'%')
                    ->orWhereHas('supplier', fn (Builder $supplierQuery) => $supplierQuery->where('name', 'like', '%'.$term.'%'))),
                filters: [
                    $this->filterDefinition('status', 'Status', $this->optionsFromValues([SupplierBill::STATUS_DRAFT, SupplierBill::STATUS_APPROVED, SupplierBill::STATUS_PARTIALLY_PAID, SupplierBill::STATUS_PAID, SupplierBill::STATUS_VOID]), fn (Builder $query, string $value) => $query->where('status', $value)),
                    $this->filterDefinition('match_status', 'Match', $this->optionsFromValues([SupplierBill::MATCH_STATUS_MATCHED, SupplierBill::MATCH_STATUS_WITHIN_TOLERANCE, SupplierBill::MATCH_STATUS_PARTIAL, SupplierBill::MATCH_STATUS_EXCEPTION]), fn (Builder $query, string $value) => $query->where('match_status', $value)),
                ],
            )),
            'supplier-payments' => $this->tablePage($entry, SupplierPayment::query()->with('supplierBill')->latest('paid_at'), $request, [
                ['label' => 'Payment', 'value' => fn (SupplierPayment $payment) => $payment->payment_number],
                ['label' => 'Bill', 'value' => fn (SupplierPayment $payment) => $payment->supplierBill?->bill_number ?: 'Not linked'],
                ['label' => 'Method', 'value' => fn (SupplierPayment $payment) => str($payment->payment_method)->headline()],
                ['label' => 'Amount', 'value' => fn (SupplierPayment $payment) => number_format((float) $payment->amount, 2)],
                ['label' => 'Paid at', 'value' => fn (SupplierPayment $payment) => $payment->paid_at?->format('Y-m-d H:i') ?: 'Pending'],
            ], [
                $this->summaryMetric('Payments today', SupplierPayment::query()->whereDate('paid_at', now()->toDateString())->count(), 'Supplier payments posted today.'),
                $this->summaryMetric('Cash disbursed today', number_format((float) SupplierPayment::query()->whereDate('paid_at', now()->toDateString())->sum('amount'), 2), 'Amount paid out today.'),
            ], $this->tableOptions(
                searchPlaceholder: 'Search payment, reference, or bill',
                searchCallback: fn (Builder $query, string $term) => $query->where(fn (Builder $builder) => $builder
                    ->where('payment_number', 'like', '%'.$term.'%')
                    ->orWhere('reference', 'like', '%'.$term.'%')
                    ->orWhereHas('supplierBill', fn (Builder $billQuery) => $billQuery->where('bill_number', 'like', '%'.$term.'%'))),
                filters: [
                    $this->filterDefinition('payment_method', 'Method', $this->optionsFromValues(['cash', 'card', 'bank_transfer', 'check']), fn (Builder $query, string $value) => $query->where('payment_method', $value)),
                    $this->filterDefinition('status', 'Status', $this->optionsFromValues([SupplierPayment::STATUS_POSTED, SupplierPayment::STATUS_VOID]), fn (Builder $query, string $value) => $query->where('status', $value)),
                ],
            )),
            'finance-ar-aging' => $this->agingPage($entry, $this->financeReportingService->arAgingSummary(now()->toDateString())),
            'finance-ap-aging' => $this->agingPage($entry, $this->financeReportingService->apAgingSummary(now()->toDateString())),
            'bank-accounts' => $this->tablePage($entry, BankAccount::query()->latest('id'), $request, [
                ['label' => 'Account', 'value' => fn (BankAccount $account) => $account->name],
                ['label' => 'Bank', 'value' => fn (BankAccount $account) => $account->bank_name],
                ['label' => 'Currency', 'value' => fn (BankAccount $account) => $account->currency_code],
                ['label' => 'Balance', 'value' => fn (BankAccount $account) => number_format((float) $account->current_balance, 2)],
                ['label' => 'Active', 'value' => fn (BankAccount $account) => $account->is_active ? 'Yes' : 'No'],
            ], [
                $this->summaryMetric('Active accounts', BankAccount::query()->where('is_active', true)->count(), 'Bank accounts currently enabled.'),
                $this->summaryMetric('Total bank balance', number_format((float) BankAccount::query()->sum('current_balance'), 2), 'Current recorded bank cash.'),
            ], $this->tableOptions(
                searchPlaceholder: 'Search account or bank name',
                searchCallback: fn (Builder $query, string $term) => $query->where(fn (Builder $builder) => $builder
                    ->where('name', 'like', '%'.$term.'%')
                    ->orWhere('bank_name', 'like', '%'.$term.'%')),
                filters: [
                    $this->filterDefinition('currency_code', 'Currency', BankAccount::query()->select('currency_code')->whereNotNull('currency_code')->distinct()->orderBy('currency_code')->get()->map(fn (BankAccount $account) => ['value' => $account->currency_code, 'label' => $account->currency_code])->all(), fn (Builder $query, string $value) => $query->where('currency_code', $value)),
                    $this->filterDefinition('is_active', 'Active', [['value' => '1', 'label' => 'Yes'], ['value' => '0', 'label' => 'No']], fn (Builder $query, string $value) => $query->where('is_active', $value === '1')),
                ],
            )),
            'bank-reconciliations' => $this->tablePage($entry, BankReconciliation::query()->with('bankAccount')->latest('period_end'), $request, [
                ['label' => 'Bank account', 'value' => fn (BankReconciliation $reconciliation) => $reconciliation->bankAccount?->name ?: 'Not linked'],
                ['label' => 'Period end', 'value' => fn (BankReconciliation $reconciliation) => $reconciliation->period_end?->toDateString() ?: 'Not set'],
                ['label' => 'Status', 'value' => fn (BankReconciliation $reconciliation) => str($reconciliation->status)->headline()],
                ['label' => 'Cleared balance', 'value' => fn (BankReconciliation $reconciliation) => number_format((float) $reconciliation->cleared_balance, 2)],
            ], [
                $this->summaryMetric('Open reconciliations', BankReconciliation::query()->where('status', BankReconciliation::STATUS_OPEN)->count(), 'Reconciliations still in progress.'),
                $this->summaryMetric('Completed', BankReconciliation::query()->where('status', BankReconciliation::STATUS_COMPLETED)->count(), 'Closed reconciliation periods.'),
            ], $this->tableOptions(
                searchPlaceholder: 'Search bank account',
                searchCallback: fn (Builder $query, string $term) => $query->whereHas('bankAccount', fn (Builder $bankQuery) => $bankQuery->where('name', 'like', '%'.$term.'%')),
                filters: [
                    $this->filterDefinition('status', 'Status', $this->optionsFromValues([BankReconciliation::STATUS_OPEN, BankReconciliation::STATUS_COMPLETED]), fn (Builder $query, string $value) => $query->where('status', $value)),
                ],
            )),
            'housekeeping-tasks' => $this->tablePage($entry, HousekeepingTask::query()->with(['room', 'property'])->latest('scheduled_for'), $request, [
                ['label' => 'Task type', 'value' => fn (HousekeepingTask $task) => str($task->task_type)->headline()],
                ['label' => 'Room', 'value' => fn (HousekeepingTask $task) => $task->room?->room_number ?: 'Unassigned'],
                ['label' => 'Property', 'value' => fn (HousekeepingTask $task) => $task->property?->name ?: 'Not linked'],
                ['label' => 'Status', 'value' => fn (HousekeepingTask $task) => str($task->status)->headline()],
                ['label' => 'Priority', 'value' => fn (HousekeepingTask $task) => str($task->priority ?? 'standard')->headline()],
            ], [
                $this->summaryMetric('Pending', HousekeepingTask::query()->where('status', HousekeepingTask::STATUS_PENDING)->count(), 'Tasks waiting to start.'),
                $this->summaryMetric('In progress', HousekeepingTask::query()->where('status', HousekeepingTask::STATUS_IN_PROGRESS)->count(), 'Tasks currently being worked.'),
            ], $this->tableOptions(
                searchPlaceholder: 'Search task, room, or property',
                searchCallback: fn (Builder $query, string $term) => $query->where(fn (Builder $builder) => $builder
                    ->where('task_type', 'like', '%'.$term.'%')
                    ->orWhereHas('room', fn (Builder $roomQuery) => $roomQuery->where('room_number', 'like', '%'.$term.'%'))
                    ->orWhereHas('property', fn (Builder $propertyQuery) => $propertyQuery->where('name', 'like', '%'.$term.'%'))),
                filters: [
                    $this->filterDefinition('status', 'Status', $this->optionsFromValues([HousekeepingTask::STATUS_PENDING, HousekeepingTask::STATUS_IN_PROGRESS, HousekeepingTask::STATUS_COMPLETED, HousekeepingTask::STATUS_INSPECTED]), fn (Builder $query, string $value) => $query->where('status', $value)),
                    $this->filterDefinition('priority', 'Priority', $this->optionsFromValues(['high', 'standard', 'low']), fn (Builder $query, string $value) => $query->where('priority', $value)),
                    $this->filterDefinition('property_id', 'Property', $this->propertyOptions(), fn (Builder $query, string $value) => $query->where('property_id', $value)),
                ],
                bulkActions: [
                    ['value' => 'mark_in_progress', 'label' => 'Mark in progress'],
                    ['value' => 'mark_completed', 'label' => 'Mark completed'],
                    ['value' => 'assign_user', 'label' => 'Assign user'],
                ],
                bulkUserOptions: $this->userOptions(),
            )),
            'maintenance-requests' => $this->tablePage($entry, MaintenanceRequest::query()->with(['room', 'property'])->latest('reported_at'), $request, [
                ['label' => 'Request', 'value' => fn (MaintenanceRequest $request) => $request->title],
                ['label' => 'Room', 'value' => fn (MaintenanceRequest $request) => $request->room?->room_number ?: 'Common area'],
                ['label' => 'Property', 'value' => fn (MaintenanceRequest $request) => $request->property?->name ?: 'Not linked'],
                ['label' => 'Status', 'value' => fn (MaintenanceRequest $request) => str($request->status)->headline()],
                ['label' => 'Priority', 'value' => fn (MaintenanceRequest $request) => str($request->priority)->headline()],
            ], [
                $this->summaryMetric('Open requests', MaintenanceRequest::query()->where('status', MaintenanceRequest::STATUS_OPEN)->count(), 'Engineering issues newly logged.'),
                $this->summaryMetric('Urgent requests', MaintenanceRequest::query()->where('priority', MaintenanceRequest::PRIORITY_URGENT)->count(), 'Priority issues needing immediate response.'),
            ], $this->tableOptions(
                searchPlaceholder: 'Search request, room, or property',
                searchCallback: fn (Builder $query, string $term) => $query->where(fn (Builder $builder) => $builder
                    ->where('title', 'like', '%'.$term.'%')
                    ->orWhereHas('room', fn (Builder $roomQuery) => $roomQuery->where('room_number', 'like', '%'.$term.'%'))
                    ->orWhereHas('property', fn (Builder $propertyQuery) => $propertyQuery->where('name', 'like', '%'.$term.'%'))),
                filters: [
                    $this->filterDefinition('status', 'Status', $this->optionsFromValues([MaintenanceRequest::STATUS_OPEN, MaintenanceRequest::STATUS_IN_PROGRESS, MaintenanceRequest::STATUS_COMPLETED, MaintenanceRequest::STATUS_CANCELLED]), fn (Builder $query, string $value) => $query->where('status', $value)),
                    $this->filterDefinition('priority', 'Priority', $this->optionsFromValues([MaintenanceRequest::PRIORITY_LOW, MaintenanceRequest::PRIORITY_MEDIUM, MaintenanceRequest::PRIORITY_HIGH, MaintenanceRequest::PRIORITY_URGENT]), fn (Builder $query, string $value) => $query->where('priority', $value)),
                    $this->filterDefinition('property_id', 'Property', $this->propertyOptions(), fn (Builder $query, string $value) => $query->where('property_id', $value)),
                ],
                bulkActions: [
                    ['value' => 'mark_in_progress', 'label' => 'Mark in progress'],
                    ['value' => 'mark_completed', 'label' => 'Mark completed'],
                    ['value' => 'assign_user', 'label' => 'Assign user'],
                ],
                bulkUserOptions: $this->userOptions(),
            )),
            'preventive-maintenance-schedules' => $this->tablePage($entry, PreventiveMaintenanceSchedule::query()->with(['room', 'property'])->latest('next_due_at'), $request, [
                ['label' => 'Schedule', 'value' => fn (PreventiveMaintenanceSchedule $schedule) => $schedule->title],
                ['label' => 'Room', 'value' => fn (PreventiveMaintenanceSchedule $schedule) => $schedule->room?->room_number ?: 'Common area'],
                ['label' => 'Property', 'value' => fn (PreventiveMaintenanceSchedule $schedule) => $schedule->property?->name ?: 'Not linked'],
                ['label' => 'Frequency', 'value' => fn (PreventiveMaintenanceSchedule $schedule) => $schedule->frequency_days.' days'],
                ['label' => 'Next due', 'value' => fn (PreventiveMaintenanceSchedule $schedule) => $schedule->next_due_at?->format('Y-m-d H:i') ?: 'Not scheduled'],
            ], [
                $this->summaryMetric('Active schedules', PreventiveMaintenanceSchedule::query()->where('is_active', true)->count(), 'Recurring schedules currently enabled.'),
                $this->summaryMetric('Due in 7 days', PreventiveMaintenanceSchedule::query()->whereBetween('next_due_at', [now(), now()->copy()->addDays(7)])->count(), 'Schedules about to generate new work.'),
            ], $this->tableOptions(
                searchPlaceholder: 'Search schedule, room, or property',
                searchCallback: fn (Builder $query, string $term) => $query->where(fn (Builder $builder) => $builder
                    ->where('title', 'like', '%'.$term.'%')
                    ->orWhereHas('room', fn (Builder $roomQuery) => $roomQuery->where('room_number', 'like', '%'.$term.'%'))
                    ->orWhereHas('property', fn (Builder $propertyQuery) => $propertyQuery->where('name', 'like', '%'.$term.'%'))),
                filters: [
                    $this->filterDefinition('is_active', 'Active', [['value' => '1', 'label' => 'Yes'], ['value' => '0', 'label' => 'No']], fn (Builder $query, string $value) => $query->where('is_active', $value === '1')),
                    $this->filterDefinition('property_id', 'Property', $this->propertyOptions(), fn (Builder $query, string $value) => $query->where('property_id', $value)),
                ],
                bulkActions: [
                    ['value' => 'activate', 'label' => 'Activate'],
                    ['value' => 'deactivate', 'label' => 'Deactivate'],
                ],
            )),
            'pos-cashier-shifts' => $this->tablePage($entry, PosCashierShift::query()->with(['property', 'cashier'])->latest('opened_at'), $request, [
                ['label' => 'Shift', 'value' => fn (PosCashierShift $shift) => $shift->shift_number],
                ['label' => 'Property', 'value' => fn (PosCashierShift $shift) => $shift->property?->name ?: 'Not linked'],
                ['label' => 'Cashier', 'value' => fn (PosCashierShift $shift) => $shift->cashier?->name ?: 'Unassigned'],
                ['label' => 'Status', 'value' => fn (PosCashierShift $shift) => str($shift->status)->headline()],
                ['label' => 'Opened', 'value' => fn (PosCashierShift $shift) => $shift->opened_at?->format('Y-m-d H:i') ?: 'Pending'],
            ], [
                $this->summaryMetric('Open shifts', PosCashierShift::query()->where('status', PosCashierShift::STATUS_OPEN)->count(), 'Registers currently trading.'),
                $this->summaryMetric('Closed shifts', PosCashierShift::query()->where('status', PosCashierShift::STATUS_CLOSED)->count(), 'Registers already reconciled.'),
            ], $this->tableOptions(
                searchPlaceholder: 'Search shift number or cashier',
                searchCallback: fn (Builder $query, string $term) => $query->where(fn (Builder $builder) => $builder
                    ->where('shift_number', 'like', '%'.$term.'%')
                    ->orWhereHas('cashier', fn (Builder $cashierQuery) => $cashierQuery->where('name', 'like', '%'.$term.'%'))),
                filters: [
                    $this->filterDefinition('status', 'Status', $this->optionsFromValues([PosCashierShift::STATUS_OPEN, PosCashierShift::STATUS_CLOSED]), fn (Builder $query, string $value) => $query->where('status', $value)),
                    $this->filterDefinition('property_id', 'Property', $this->propertyOptions(), fn (Builder $query, string $value) => $query->where('property_id', $value)),
                ],
            )),
            'pos-orders' => $this->tablePage($entry, PosOrder::query()->with('property')->latest('paid_at'), $request, [
                ['label' => 'Order', 'value' => fn (PosOrder $order) => $order->order_number],
                ['label' => 'Property', 'value' => fn (PosOrder $order) => $order->property?->name ?: 'Not linked'],
                ['label' => 'Status', 'value' => fn (PosOrder $order) => str($order->status)->headline()],
                ['label' => 'Service', 'value' => fn (PosOrder $order) => str($order->service_location ?? 'unspecified')->headline()],
                ['label' => 'Total', 'value' => fn (PosOrder $order) => number_format((float) $order->total_amount, 2)],
            ], [
                $this->summaryMetric('Paid orders today', PosOrder::query()->where('status', PosOrder::STATUS_PAID)->whereDate('paid_at', now()->toDateString())->count(), 'Completed POS transactions today.'),
                $this->summaryMetric('POS sales today', number_format((float) PosOrder::query()->where('status', PosOrder::STATUS_PAID)->whereDate('paid_at', now()->toDateString())->sum('total_amount'), 2), 'Revenue from POS today.'),
            ], $this->tableOptions(
                searchPlaceholder: 'Search order number or property',
                searchCallback: fn (Builder $query, string $term) => $query->where(fn (Builder $builder) => $builder
                    ->where('order_number', 'like', '%'.$term.'%')
                    ->orWhereHas('property', fn (Builder $propertyQuery) => $propertyQuery->where('name', 'like', '%'.$term.'%'))),
                filters: [
                    $this->filterDefinition('status', 'Status', $this->optionsFromValues([PosOrder::STATUS_OPEN, PosOrder::STATUS_PAID, PosOrder::STATUS_VOID]), fn (Builder $query, string $value) => $query->where('status', $value)),
                    $this->filterDefinition('service_location', 'Service', $this->optionsFromValues(['restaurant', 'bar', 'room_service', 'pool']), fn (Builder $query, string $value) => $query->where('service_location', $value)),
                ],
            )),
            'inventory-items' => $this->tablePage($entry, InventoryItem::query()->latest('id'), $request, [
                ['label' => 'Item', 'value' => fn (InventoryItem $item) => $item->name],
                ['label' => 'SKU', 'value' => fn (InventoryItem $item) => $item->sku ?: 'Not set'],
                ['label' => 'Category', 'value' => fn (InventoryItem $item) => str($item->category ?? 'uncategorized')->headline()],
                ['label' => 'On hand', 'value' => fn (InventoryItem $item) => number_format((float) $item->current_quantity, 2)],
                ['label' => 'Reorder', 'value' => fn (InventoryItem $item) => number_format((float) $item->reorder_level, 2)],
            ], [
                $this->summaryMetric('Inventory items', InventoryItem::query()->count(), 'Cataloged items for tenant operations.'),
                $this->summaryMetric('Low stock', InventoryItem::query()->where('reorder_level', '>', 0)->whereColumn('current_quantity', '<=', 'reorder_level')->count(), 'Items at or below reorder threshold.'),
            ], $this->tableOptions(
                searchPlaceholder: 'Search item name or SKU',
                searchCallback: fn (Builder $query, string $term) => $query->where(fn (Builder $builder) => $builder
                    ->where('name', 'like', '%'.$term.'%')
                    ->orWhere('sku', 'like', '%'.$term.'%')),
                filters: [
                    $this->filterDefinition('category', 'Category', InventoryItem::query()->select('category')->whereNotNull('category')->distinct()->orderBy('category')->get()->map(fn (InventoryItem $item) => ['value' => $item->category, 'label' => str($item->category)->headline()->toString()])->all(), fn (Builder $query, string $value) => $query->where('category', $value)),
                    $this->filterDefinition('property_id', 'Property', $this->propertyOptions(), fn (Builder $query, string $value) => $query->where('property_id', $value)),
                ],
            )),
            'inventory-movements' => $this->tablePage($entry, InventoryMovement::query()->with('item')->latest('moved_at'), $request, [
                ['label' => 'Movement', 'value' => fn (InventoryMovement $movement) => str($movement->movement_type)->headline()],
                ['label' => 'Item', 'value' => fn (InventoryMovement $movement) => $movement->item?->name ?: 'Unknown item'],
                ['label' => 'Quantity', 'value' => fn (InventoryMovement $movement) => number_format((float) $movement->quantity_change, 2)],
                ['label' => 'Unit cost', 'value' => fn (InventoryMovement $movement) => number_format((float) ($movement->unit_cost ?? 0), 2)],
                ['label' => 'Moved at', 'value' => fn (InventoryMovement $movement) => $movement->moved_at?->format('Y-m-d H:i') ?: 'Pending'],
            ], [
                $this->summaryMetric('Receipts', InventoryMovement::query()->where('movement_type', InventoryMovement::TYPE_RECEIPT)->count(), 'Inbound stock receipts recorded.'),
                $this->summaryMetric('Issues', InventoryMovement::query()->where('movement_type', InventoryMovement::TYPE_ISSUE)->count(), 'Stock issued to operations.'),
                $this->summaryMetric('Wastage', InventoryMovement::query()->where('movement_type', InventoryMovement::TYPE_WASTAGE)->count(), 'Write-offs and wastage events.'),
            ], $this->tableOptions(
                searchPlaceholder: 'Search item, movement type, or notes',
                searchCallback: fn (Builder $query, string $term) => $query->where(fn (Builder $builder) => $builder
                    ->where('movement_type', 'like', '%'.$term.'%')
                    ->orWhere('notes', 'like', '%'.$term.'%')
                    ->orWhereHas('item', fn (Builder $itemQuery) => $itemQuery->where('name', 'like', '%'.$term.'%')->orWhere('sku', 'like', '%'.$term.'%'))),
                filters: [
                    $this->filterDefinition('movement_type', 'Movement Type', $this->optionsFromValues([InventoryMovement::TYPE_RECEIPT, InventoryMovement::TYPE_ISSUE, InventoryMovement::TYPE_ADJUSTMENT, InventoryMovement::TYPE_WASTAGE]), fn (Builder $query, string $value) => $query->where('movement_type', $value)),
                ],
            )),
            'purchase-orders' => $this->tablePage($entry, PurchaseOrder::query()->with('supplier')->latest('order_date'), $request, [
                ['label' => 'PO', 'value' => fn (PurchaseOrder $order) => $order->purchase_order_number],
                ['label' => 'Supplier', 'value' => fn (PurchaseOrder $order) => $order->supplier?->name ?: 'Not linked'],
                ['label' => 'Status', 'value' => fn (PurchaseOrder $order) => str($order->status)->headline()],
                ['label' => 'Match', 'value' => fn (PurchaseOrder $order) => str($order->match_status)->headline()],
                ['label' => 'Total', 'value' => fn (PurchaseOrder $order) => number_format((float) $order->total_amount, 2)],
            ], [
                $this->summaryMetric('Draft POs', PurchaseOrder::query()->where('status', PurchaseOrder::STATUS_DRAFT)->count(), 'Orders still waiting for approval.'),
                $this->summaryMetric('Pending approval steps', PurchaseOrderApproval::query()->where('status', PurchaseOrderApproval::STATUS_PENDING)->count(), 'Approval chain tasks still open.'),
            ], $this->tableOptions(
                searchPlaceholder: 'Search PO or supplier',
                searchCallback: fn (Builder $query, string $term) => $query->where(fn (Builder $builder) => $builder
                    ->where('purchase_order_number', 'like', '%'.$term.'%')
                    ->orWhereHas('supplier', fn (Builder $supplierQuery) => $supplierQuery->where('name', 'like', '%'.$term.'%'))),
                filters: [
                    $this->filterDefinition('status', 'Status', $this->optionsFromValues([PurchaseOrder::STATUS_DRAFT, PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_PARTIALLY_RECEIVED, PurchaseOrder::STATUS_RECEIVED, PurchaseOrder::STATUS_REJECTED]), fn (Builder $query, string $value) => $query->where('status', $value)),
                    $this->filterDefinition('match_status', 'Match', $this->optionsFromValues([PurchaseOrder::MATCH_STATUS_UNMATCHED, PurchaseOrder::MATCH_STATUS_PARTIAL, PurchaseOrder::MATCH_STATUS_MATCHED, PurchaseOrder::MATCH_STATUS_WITHIN_TOLERANCE, PurchaseOrder::MATCH_STATUS_EXCEPTION]), fn (Builder $query, string $value) => $query->where('match_status', $value)),
                ],
            )),
            default => throw new HttpException(404),
        };
    }

    public function moduleCreateForm(User $user, string $module): array
    {
        $entry = $this->findModule($user, $module);
        $createPermission = $entry['create_permission'] ?? null;

        if (! $createPermission || ! $user->can($createPermission)) {
            throw new HttpException(403);
        }

        return match ($module) {
            'properties' => $this->propertyForm($entry),
            'rooms' => $this->roomForm($entry),
            'guests' => $this->guestForm($entry),
            'reservations' => [
                'module' => $entry,
                'submit_label' => 'Create reservation',
                'fields' => [
                    $this->selectField('property_id', 'Property', $this->propertyOptions(), true),
                    $this->selectField('room_id', 'Room', $this->roomOptions(), true),
                    $this->selectField('guest_profile_id', 'Guest', $this->guestOptions(), true),
                    $this->textField('booking_source', 'Booking source', 'online'),
                    $this->textField('currency_code', 'Currency', 'USD'),
                    $this->selectField('status', 'Status', $this->optionsFromValues([Reservation::STATUS_PENDING, Reservation::STATUS_CONFIRMED]), true),
                    $this->textField('check_in_date', 'Check-in date', now()->toDateString(), 'date', true),
                    $this->textField('check_out_date', 'Check-out date', now()->copy()->addDay()->toDateString(), 'date', true),
                    $this->textField('adult_count', 'Adults', '1', 'number', true),
                    $this->textField('child_count', 'Children', '0', 'number'),
                    $this->textField('rate_amount', 'Rate amount', '0', 'number'),
                    $this->textField('tax_amount', 'Tax amount', '0', 'number'),
                    $this->textareaField('special_requests', 'Special requests'),
                ],
            ],
            'folios' => [
                'module' => $entry,
                'submit_label' => 'Open folio',
                'fields' => [
                    $this->selectField('reservation_id', 'Reservation', $this->reservationOptions(), true),
                ],
            ],
            'invoices' => [
                'module' => $entry,
                'submit_label' => 'Issue invoice',
                'fields' => [
                    $this->selectField('folio_id', 'Folio', $this->folioOptions(), true),
                    $this->textField('issue_date', 'Issue date', now()->toDateString(), 'date'),
                    $this->textField('due_date', 'Due date', now()->toDateString(), 'date'),
                    $this->textareaField('notes', 'Notes'),
                ],
            ],
            'supplier-bills' => [
                'module' => $entry,
                'submit_label' => 'Create supplier bill',
                'fields' => [
                    $this->selectField('supplier_id', 'Supplier', $this->supplierOptions(), true),
                    $this->selectField('purchase_order_id', 'Purchase order', $this->purchaseOrderOptions(false)),
                    $this->textField('currency_code', 'Currency', 'USD'),
                    $this->textField('bill_date', 'Bill date', now()->toDateString(), 'date', true),
                    $this->textField('due_date', 'Due date', now()->copy()->addDays(7)->toDateString(), 'date', true),
                    $this->textareaField('description', 'Description'),
                    $this->selectField('lines[0][purchase_order_line_id]', 'PO line', $this->purchaseOrderLineOptions()),
                    $this->selectField('lines[0][inventory_item_id]', 'Inventory item', $this->inventoryItemOptions()),
                    $this->textField('lines[0][description]', 'Line description', '', 'text', true),
                    $this->textField('lines[0][quantity]', 'Quantity', '1', 'number', true),
                    $this->textField('lines[0][unit_cost]', 'Unit cost', '0', 'number', true),
                    $this->textField('lines[0][tax_amount]', 'Tax amount', '0', 'number'),
                ],
            ],
            'maintenance-requests' => [
                ...$this->maintenanceRequestForm($entry),
            ],
            'housekeeping-tasks' => [
                ...$this->housekeepingTaskForm($entry),
            ],
            'preventive-maintenance-schedules' => [
                ...$this->preventiveScheduleForm($entry),
            ],
            'pos-cashier-shifts' => [
                'module' => $entry,
                'submit_label' => 'Open cashier shift',
                'fields' => [
                    $this->selectField('property_id', 'Property', $this->propertyOptions(), true),
                    $this->selectField('user_id', 'Cashier', $this->userOptions(), true),
                    $this->textField('opening_cash_amount', 'Opening cash amount', '0', 'number'),
                    $this->textareaField('notes', 'Notes'),
                ],
            ],
            'purchase-orders' => [
                'module' => $entry,
                'submit_label' => 'Create purchase order',
                'fields' => [
                    $this->selectField('property_id', 'Property', $this->propertyOptions()),
                    $this->selectField('supplier_id', 'Supplier', $this->supplierOptions(), true),
                    $this->textField('currency_code', 'Currency', 'USD'),
                    $this->textField('expected_delivery_date', 'Expected delivery', now()->copy()->addDays(3)->toDateString(), 'date'),
                    $this->textField('quantity_tolerance_percent', 'Quantity tolerance %', '0', 'number'),
                    $this->textField('amount_tolerance_percent', 'Amount tolerance %', '0', 'number'),
                    $this->textareaField('notes', 'Notes'),
                    $this->selectField('approval_steps[0][approver_user_id]', 'Approver', $this->userOptions()),
                    $this->selectField('lines[0][inventory_item_id]', 'Inventory item', $this->inventoryItemOptions(), true),
                    $this->textField('lines[0][description]', 'Line description', '', 'text', true),
                    $this->textField('lines[0][ordered_quantity]', 'Ordered quantity', '1', 'number', true),
                    $this->textField('lines[0][unit_cost]', 'Unit cost', '0', 'number', true),
                    $this->textField('lines[0][tax_amount]', 'Tax amount', '0', 'number'),
                ],
            ],
            'pos-orders' => [
                'module' => $entry,
                'submit_label' => 'Create POS order',
                'fields' => [
                    $this->selectField('property_id', 'Property', $this->propertyOptions(), true),
                    $this->selectField('cashier_shift_id', 'Cashier shift', $this->cashierShiftOptions()),
                    $this->selectField('reservation_id', 'Reservation', $this->reservationOptions()),
                    $this->selectField('folio_id', 'Folio', $this->folioOptions()),
                    $this->textField('payment_method', 'Payment method', 'cash'),
                    $this->textField('service_location', 'Service location', 'restaurant'),
                    $this->checkboxField('charge_to_room', 'Charge to room'),
                    $this->textareaField('notes', 'Notes'),
                    $this->selectField('lines[0][inventory_item_id]', 'Inventory item', $this->inventoryItemOptions()),
                    $this->textField('lines[0][item_name]', 'Item name'),
                    $this->textField('lines[0][category]', 'Category'),
                    $this->textField('lines[0][kitchen_station]', 'Kitchen station'),
                    $this->textField('lines[0][quantity]', 'Quantity', '1', 'number', true),
                    $this->textField('lines[0][unit_price]', 'Unit price', '0', 'number', true),
                    $this->textField('lines[0][tax_amount]', 'Tax amount', '0', 'number'),
                ],
            ],
            'bank-accounts' => $this->bankAccountForm($entry),
            'bank-reconciliations' => [
                'module' => $entry,
                'submit_label' => 'Create bank reconciliation',
                'fields' => [
                    $this->selectField('bank_account_id', 'Bank account', $this->bankAccountOptions(), true),
                    $this->textField('period_start', 'Period start', now()->startOfMonth()->toDateString(), 'date', true),
                    $this->textField('period_end', 'Period end', now()->toDateString(), 'date', true),
                    $this->textField('statement_ending_balance', 'Statement ending balance', '0', 'number', true),
                    $this->textField('book_ending_balance', 'Book ending balance', '0', 'number'),
                    $this->textField('status', 'Status', BankReconciliation::STATUS_OPEN),
                    $this->textareaField('notes', 'Notes'),
                ],
            ],
            'inventory-items' => $this->inventoryItemForm($entry),
            'suppliers' => $this->supplierForm($entry),
            default => throw new HttpException(404),
        };
    }

    public function moduleEditForm(User $user, string $module, int|string $recordId): array
    {
        $entry = $this->findModule($user, $module);
        $record = $this->editableRecord($module, $recordId);
        $editPermission = $entry['update_permission'] ?? $entry['create_permission'] ?? null;

        if (! $editPermission || ! $user->can($editPermission)) {
            throw new HttpException(403);
        }

        return match ($module) {
            'properties' => $this->propertyForm($entry, $record),
            'rooms' => $this->roomForm($entry, $record),
            'guests' => $this->guestForm($entry, $record),
            'suppliers' => $this->supplierForm($entry, $record),
            'bank-accounts' => $this->bankAccountForm($entry, $record),
            'inventory-items' => $this->inventoryItemForm($entry, $record),
            'maintenance-requests' => $this->maintenanceRequestForm($entry, $record),
            'housekeeping-tasks' => $this->housekeepingTaskForm($entry, $record),
            'preventive-maintenance-schedules' => $this->preventiveScheduleForm($entry, $record),
            default => throw new HttpException(404),
        } + [
            'form_action' => route('admin.workspace.records.update', ['module' => $module, 'record' => $record->getKey()]),
            'form_method' => 'PUT',
            'form_title' => 'Edit '.$entry['label'],
            'record_title' => $this->recordTitle($record),
            'cancel_url' => route('admin.workspace.records.show', ['module' => $module, 'record' => $record->getKey()]),
        ];
    }

    public function storeModule(User $user, string $module, Request $request): array
    {
        $entry = $this->findModule($user, $module);
        $createPermission = $entry['create_permission'] ?? null;

        if (! $createPermission || ! $user->can($createPermission)) {
            throw new HttpException(403);
        }

        return match ($module) {
            'properties' => $this->storeProperty($request),
            'rooms' => $this->storeRoom($request),
            'guests' => $this->storeGuest($request),
            'reservations' => $this->storeReservation($request),
            'folios' => $this->storeFolio($request),
            'invoices' => $this->storeInvoice($request),
            'supplier-bills' => $this->storeSupplierBill($request),
            'suppliers' => $this->storeSupplier($request),
            'bank-accounts' => $this->storeBankAccount($request),
            'bank-reconciliations' => $this->storeBankReconciliation($request),
            'housekeeping-tasks' => $this->storeHousekeepingTask($request),
            'maintenance-requests' => $this->storeMaintenanceRequest($request, $user),
            'preventive-maintenance-schedules' => $this->storePreventiveMaintenanceSchedule($request),
            'pos-cashier-shifts' => $this->storeCashierShift($request),
            'inventory-items' => $this->storeInventoryItem($request),
            'purchase-orders' => $this->storePurchaseOrder($request),
            'pos-orders' => $this->storePosOrder($request),
            default => throw new HttpException(404),
        };
    }

    public function updateModule(User $user, string $module, int|string $recordId, Request $request): array
    {
        $entry = $this->findModule($user, $module);
        $editPermission = $entry['update_permission'] ?? $entry['create_permission'] ?? null;

        if (! $editPermission || ! $user->can($editPermission)) {
            throw new HttpException(403);
        }

        return match ($module) {
            'properties' => $this->updateProperty($this->editableRecord($module, $recordId), $request),
            'rooms' => $this->updateRoom($this->editableRecord($module, $recordId), $request),
            'guests' => $this->updateGuest($this->editableRecord($module, $recordId), $request),
            'suppliers' => $this->updateSupplier($this->editableRecord($module, $recordId), $request),
            'bank-accounts' => $this->updateBankAccount($this->editableRecord($module, $recordId), $request),
            'inventory-items' => $this->updateInventoryItem($this->editableRecord($module, $recordId), $request),
            'maintenance-requests' => $this->updateMaintenanceRequest($this->editableRecord($module, $recordId), $request),
            'housekeeping-tasks' => $this->updateHousekeepingTask($this->editableRecord($module, $recordId), $request),
            'preventive-maintenance-schedules' => $this->updatePreventiveSchedule($this->editableRecord($module, $recordId), $request),
            default => throw new HttpException(404),
        };
    }

    public function performBulkAction(User $user, string $module, Request $request): array
    {
        $entry = $this->findModule($user, $module);
        $updatePermission = $entry['update_permission'] ?? null;

        if (! $updatePermission || ! $user->can($updatePermission)) {
            throw new HttpException(403);
        }

        $validated = $request->validate([
            'record_ids' => ['required', 'array', 'min:1'],
            'record_ids.*' => ['integer'],
            'bulk_action' => ['required', 'string', 'max:50'],
            'bulk_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ]);

        $recordIds = array_values(array_unique(array_map('intval', $validated['record_ids'])));

        return match ($module) {
            'maintenance-requests' => $this->bulkUpdateMaintenanceRequests($recordIds, $validated),
            'housekeeping-tasks' => $this->bulkUpdateHousekeepingTasks($recordIds, $validated),
            'preventive-maintenance-schedules' => $this->bulkUpdatePreventiveSchedules($recordIds, $validated),
            default => throw new HttpException(404),
        };
    }

    protected function badgeCounts(): array
    {
        return [
            'open_housekeeping' => HousekeepingTask::query()
                ->whereIn('status', [HousekeepingTask::STATUS_PENDING, HousekeepingTask::STATUS_IN_PROGRESS])
                ->count(),
            'open_maintenance' => MaintenanceRequest::query()
                ->whereIn('status', [MaintenanceRequest::STATUS_OPEN, MaintenanceRequest::STATUS_IN_PROGRESS])
                ->count(),
            'pending_approvals' => PurchaseOrderApproval::query()
                ->where('status', PurchaseOrderApproval::STATUS_PENDING)
                ->count(),
            'supplier_bill_exceptions' => SupplierBill::query()
                ->where('match_status', SupplierBill::MATCH_STATUS_EXCEPTION)
                ->count(),
            'low_stock_items' => InventoryItem::query()
                ->where('reorder_level', '>', 0)
                ->whereColumn('current_quantity', '<=', 'reorder_level')
                ->count(),
            'open_tickets' => SupportTicket::query()
                ->whereIn('status', [
                    SupportTicket::STATUS_OPEN,
                    SupportTicket::STATUS_IN_PROGRESS,
                    SupportTicket::STATUS_WAITING_ON_CUSTOMER,
                ])
                ->count(),
        ];
    }

    protected function widgetState(User $user, array $definitions): array
    {
        $stored = $user->dashboardPreference?->widgets;

        return collect($definitions)
            ->mapWithKeys(fn (array $definition, string $key) => [$key => true])
            ->merge(is_array($stored) ? array_intersect_key($stored, $definitions) : [])
            ->all();
    }

    protected function widgetLayout(User $user, array $definitions): array
    {
        $default = array_keys($definitions);
        $stored = $user->dashboardPreference?->layout;

        if (! is_array($stored) || $stored === []) {
            return $default;
        }

        $known = array_fill_keys($default, true);
        $ordered = array_values(array_filter($stored, fn (mixed $key) => is_string($key) && isset($known[$key])));

        foreach ($default as $key) {
            if (! in_array($key, $ordered, true)) {
                $ordered[] = $key;
            }
        }

        return $ordered;
    }

    protected function statCard(string $label, mixed $value, string $description, string $icon): array
    {
        return [
            'id' => str($label)->slug()->toString(),
            'label' => $label,
            'value' => $value,
            'description' => $description,
            'icon' => $icon,
            'source' => null,
        ];
    }

    protected function moneyStatCard(string $label, float $value, string $description, string $icon): array
    {
        return $this->statCard($label, number_format($value, 2, '.', ''), $description, $icon);
    }

    protected function agingListItems(array $summary): array
    {
        return [
            ['title' => 'Current', 'meta' => $summary['buckets']['current']],
            ['title' => '1-30 days', 'meta' => $summary['buckets']['1_30']],
            ['title' => '31-60 days', 'meta' => $summary['buckets']['31_60']],
            ['title' => '61-90 days', 'meta' => $summary['buckets']['61_90']],
            ['title' => '91+ days', 'meta' => $summary['buckets']['91_plus']],
            ['title' => 'Total', 'meta' => $summary['total_balance']],
        ];
    }

    protected function moduleEntry(
        string $key,
        string $label,
        string $description,
        string $icon,
        string $permission,
        string $routeName,
        string $routeModule,
        ?string $createRouteName = null,
        ?string $createPermission = null,
        ?string $badgeKey = null,
        ?string $updatePermission = null,
    ): array {
        return array_filter([
            'key' => $key,
            'label' => $label,
            'description' => $description,
            'icon' => $icon,
            'permission' => $permission,
            'route' => route($routeName, ['module' => $routeModule]),
            'create_route' => $createRouteName ? route($createRouteName, ['module' => $routeModule]) : null,
            'create_permission' => $createPermission,
            'badge_key' => $badgeKey,
            'update_permission' => $updatePermission,
        ], fn (mixed $value) => $value !== null);
    }

    protected function findModule(User $user, string $module): array
    {
        return collect($this->moduleCatalog($user))
            ->first(fn (array $entry) => $entry['key'] === $module)
            ?? throw new HttpException(404);
    }

    protected function tablePage(array $module, Builder $query, Request $request, array $columns, array $summary = [], array $options = []): array
    {
        if (($search = trim((string) $request->input('search', ''))) !== '' && isset($options['search']['apply'])) {
            $options['search']['apply']($query, $search);
        }

        foreach ($options['filters'] ?? [] as $filter) {
            $value = $request->input($filter['name']);

            if ($value === null || $value === '') {
                continue;
            }

            $filter['apply']($query, (string) $value);
        }

        $paginator = $query->paginate(12)->withQueryString();

        return [
            'module' => $module,
            'summary' => $summary,
            'tableControls' => [
                'search' => isset($options['search']) ? [
                    'name' => 'search',
                    'placeholder' => $options['search']['placeholder'] ?? 'Search records',
                    'value' => (string) $request->input('search', ''),
                ] : null,
                'filters' => collect($options['filters'] ?? [])->map(fn (array $filter) => [
                    'name' => $filter['name'],
                    'label' => $filter['label'],
                    'options' => $filter['options'],
                    'value' => (string) $request->input($filter['name'], ''),
                ])->all(),
                'bulk_actions' => $options['bulk_actions'] ?? [],
                'bulk_user_options' => $options['bulk_user_options'] ?? [],
            ],
            'columns' => array_map(fn (array $column) => $column['label'], $columns),
            'rows' => collect($paginator->items())
                ->map(fn (mixed $record) => [
                    'id' => $record->getKey(),
                    'cells' => collect($columns)
                        ->map(fn (array $column) => $column['value']($record))
                        ->all(),
                    'url' => route('admin.workspace.records.show', ['module' => $module['key'], 'record' => $record->getKey()]),
                ])
                ->all(),
            'paginator' => $paginator,
        ];
    }

    protected function agingPage(array $module, array $summary): array
    {
        return [
            'module' => $module,
            'summary' => [
                $this->summaryMetric('As of', $summary['as_of_date'], 'Aging reference date.'),
                $this->summaryMetric('Open items', $summary['open_count'], 'Documents still outstanding.'),
                $this->summaryMetric('Total balance', $summary['total_balance'], 'Total balance across open items.'),
            ],
            'columns' => ['Document', 'Due date', 'Days past due', 'Bucket', 'Balance'],
            'rows' => collect($summary['items'])
                ->map(fn (array $item) => [
                    'id' => null,
                    'cells' => [
                        $item['number'],
                        $item['due_date'] ?: 'Not set',
                        $item['days_past_due'],
                        str($item['bucket'])->replace('_', '-')->headline()->toString(),
                        $item['balance_amount'],
                    ],
                    'url' => null,
                ])
                ->all(),
            'paginator' => null,
            'tableControls' => [
                'search' => null,
                'filters' => [],
                'bulk_actions' => [],
                'bulk_user_options' => [],
            ],
        ];
    }

    protected function summaryMetric(string $label, mixed $value, string $description): array
    {
        return compact('label', 'value', 'description');
    }

    protected function propertyForm(array $entry, ?Property $property = null): array
    {
        return [
            'module' => $entry,
            'submit_label' => $property ? 'Save property' : 'Create property',
            'fields' => [
                $this->textField('branch_code', 'Branch code', $property?->branch_code ?? '', 'text', true),
                $this->textField('name', 'Name', $property?->name ?? '', 'text', true),
                $this->textField('property_type', 'Property type', $property?->property_type ?? 'hotel', 'text', true),
                $this->textField('timezone', 'Timezone', $property?->timezone ?? 'UTC', 'text', true),
                $this->textField('currency_code', 'Currency', $property?->currency_code ?? 'USD', 'text', true),
                $this->textField('check_in_time', 'Check-in time', $property?->check_in_time ?? '14:00', 'text'),
                $this->textField('check_out_time', 'Check-out time', $property?->check_out_time ?? '12:00', 'text'),
                $this->textField('status', 'Status', $property?->status ?? 'active'),
            ],
        ];
    }

    protected function roomForm(array $entry, ?Room $room = null): array
    {
        return [
            'module' => $entry,
            'submit_label' => $room ? 'Save room' : 'Create room',
            'fields' => [
                $this->selectField('property_id', 'Property', $this->propertyOptions(), true, $room?->property_id),
                $this->selectField('room_type_id', 'Room type', $this->roomTypeOptions(), true, $room?->room_type_id),
                $this->textField('floor_label', 'Floor label', $room?->floor_label ?? ''),
                $this->textField('room_number', 'Room number', $room?->room_number ?? '', 'text', true),
                $this->textField('status', 'Status', $room?->status ?? Room::STATUS_AVAILABLE),
                $this->textField('cleaning_status', 'Cleaning status', $room?->cleaning_status ?? 'clean'),
                $this->checkboxField('is_smoking_allowed', 'Smoking allowed', (bool) ($room?->is_smoking_allowed ?? false)),
            ],
        ];
    }

    protected function guestForm(array $entry, ?GuestProfile $guest = null): array
    {
        return [
            'module' => $entry,
            'submit_label' => $guest ? 'Save guest' : 'Create guest',
            'fields' => [
                $this->textField('first_name', 'First name', $guest?->first_name ?? '', 'text', true),
                $this->textField('last_name', 'Last name', $guest?->last_name ?? '', 'text', true),
                $this->textField('email', 'Email', $guest?->email ?? ''),
                $this->textField('phone', 'Phone', $guest?->phone ?? ''),
                $this->textField('nationality', 'Nationality', $guest?->nationality ?? ''),
                $this->textField('passport_number', 'Passport number', $guest?->passport_number ?? ''),
                $this->checkboxField('is_vip', 'VIP guest', (bool) ($guest?->is_vip ?? false)),
                $this->textareaField('notes', 'Notes', $guest?->notes ?? ''),
            ],
        ];
    }

    protected function supplierForm(array $entry, ?Supplier $supplier = null): array
    {
        return [
            'module' => $entry,
            'submit_label' => $supplier ? 'Save supplier' : 'Create supplier',
            'fields' => [
                $this->textField('name', 'Name', $supplier?->name ?? '', 'text', true),
                $this->textField('email', 'Email', $supplier?->email ?? ''),
                $this->textField('phone', 'Phone', $supplier?->phone ?? ''),
                $this->textField('tax_identifier', 'Tax identifier', $supplier?->tax_identifier ?? ''),
                $this->textField('status', 'Status', $supplier?->status ?? 'active'),
                $this->textareaField('notes', 'Notes', $supplier?->notes ?? ''),
            ],
        ];
    }

    protected function bankAccountForm(array $entry, ?BankAccount $account = null): array
    {
        return [
            'module' => $entry,
            'submit_label' => $account ? 'Save bank account' : 'Create bank account',
            'fields' => [
                $this->selectField('ledger_account_id', 'Ledger account', $this->ledgerAccountOptions(), false, $account?->ledger_account_id),
                $this->textField('name', 'Name', $account?->name ?? '', 'text', true),
                $this->textField('bank_name', 'Bank name', $account?->bank_name ?? ''),
                $this->textField('account_number_last4', 'Account number last 4', $account?->account_number_last4 ?? ''),
                $this->textField('currency_code', 'Currency', $account?->currency_code ?? 'USD'),
                $this->textField('current_balance', 'Current balance', (string) ($account?->current_balance ?? '0'), 'number'),
                $this->checkboxField('is_active', 'Active account', (bool) ($account?->is_active ?? false)),
                $this->textField('opened_at', 'Opened at', $account?->opened_at?->format('Y-m-d') ?? now()->toDateString(), 'date'),
            ],
        ];
    }

    protected function inventoryItemForm(array $entry, ?InventoryItem $item = null): array
    {
        return [
            'module' => $entry,
            'submit_label' => $item ? 'Save inventory item' : 'Create inventory item',
            'fields' => [
                $this->selectField('property_id', 'Property', $this->propertyOptions(), false, $item?->property_id),
                $this->selectField('preferred_supplier_id', 'Preferred supplier', $this->supplierOptions(), false, $item?->preferred_supplier_id),
                $this->textField('sku', 'SKU', $item?->sku ?? '', 'text', true),
                $this->textField('name', 'Name', $item?->name ?? '', 'text', true),
                $this->textField('category', 'Category', $item?->category ?? ''),
                $this->textField('unit_of_measure', 'Unit of measure', $item?->unit_of_measure ?? 'unit'),
                $this->textField('current_quantity', 'Current quantity', (string) ($item?->current_quantity ?? '0'), 'number'),
                $this->textField('reorder_level', 'Reorder level', (string) ($item?->reorder_level ?? '0'), 'number'),
                $this->textField('par_level', 'Par level', (string) ($item?->par_level ?? '0'), 'number'),
                $this->textField('unit_cost', 'Unit cost', (string) ($item?->unit_cost ?? '0'), 'number'),
                $this->checkboxField('is_active', 'Active item', (bool) ($item?->is_active ?? false)),
                $this->textareaField('notes', 'Notes', $item?->notes ?? ''),
            ],
        ];
    }

    protected function maintenanceRequestForm(array $entry, ?MaintenanceRequest $request = null): array
    {
        return [
            'module' => $entry,
            'submit_label' => $request ? 'Save maintenance request' : 'Create maintenance request',
            'fields' => [
                $this->selectField('property_id', 'Property', $this->propertyOptions(), true, $request?->property_id),
                $this->selectField('room_id', 'Room', $this->roomOptions(), false, $request?->room_id),
                $this->selectField('assigned_to_user_id', 'Assign to', $this->userOptions(), false, $request?->assigned_to_user_id),
                $this->textField('title', 'Title', $request?->title ?? '', 'text', true),
                $this->textareaField('description', 'Description', $request?->description ?? ''),
                $this->textField('maintenance_category', 'Category', $request?->maintenance_category ?? 'engineering'),
                $this->selectField('priority', 'Priority', $this->optionsFromValues([
                    MaintenanceRequest::PRIORITY_LOW,
                    MaintenanceRequest::PRIORITY_MEDIUM,
                    MaintenanceRequest::PRIORITY_HIGH,
                    MaintenanceRequest::PRIORITY_URGENT,
                ]), true, $request?->priority ?? MaintenanceRequest::PRIORITY_MEDIUM),
                $this->selectField('status', 'Status', $this->optionsFromValues([
                    MaintenanceRequest::STATUS_OPEN,
                    MaintenanceRequest::STATUS_IN_PROGRESS,
                    MaintenanceRequest::STATUS_COMPLETED,
                    MaintenanceRequest::STATUS_CANCELLED,
                ]), true, $request?->status ?? MaintenanceRequest::STATUS_OPEN),
                $this->textField('scheduled_for', 'Scheduled for', $request?->scheduled_for?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i'), 'datetime-local'),
                $this->textareaField('technician_notes', 'Technician notes', $request?->technician_notes ?? ''),
            ],
        ];
    }

    protected function housekeepingTaskForm(array $entry, ?HousekeepingTask $task = null): array
    {
        return [
            'module' => $entry,
            'submit_label' => $task ? 'Save housekeeping task' : 'Create housekeeping task',
            'fields' => [
                $this->selectField('property_id', 'Property', $this->propertyOptions(), true, $task?->property_id),
                $this->selectField('room_id', 'Room', $this->roomOptions(), true, $task?->room_id),
                $this->selectField('reservation_id', 'Reservation', $this->reservationOptions(), false, $task?->reservation_id),
                $this->selectField('assigned_to_user_id', 'Assign to', $this->userOptions(), false, $task?->assigned_to_user_id),
                $this->textField('task_type', 'Task type', $task?->task_type ?? HousekeepingTask::TYPE_CHECKOUT_CLEANING, 'text', true),
                $this->selectField('status', 'Status', $this->optionsFromValues([
                    HousekeepingTask::STATUS_PENDING,
                    HousekeepingTask::STATUS_IN_PROGRESS,
                    HousekeepingTask::STATUS_COMPLETED,
                    HousekeepingTask::STATUS_INSPECTED,
                ]), true, $task?->status ?? HousekeepingTask::STATUS_PENDING),
                $this->textField('priority', 'Priority', $task?->priority ?? 'high'),
                $this->textField('linen_status', 'Linen status', $task?->linen_status ?? HousekeepingTask::LINEN_STATUS_NOT_REQUIRED),
                $this->selectField('minibar_status', 'Minibar status', $this->optionsFromValues([
                    HousekeepingTask::MINIBAR_STATUS_NOT_CHECKED,
                    HousekeepingTask::MINIBAR_STATUS_PENDING,
                    HousekeepingTask::MINIBAR_STATUS_RESTOCKED,
                ]), false, $task?->minibar_status ?? HousekeepingTask::MINIBAR_STATUS_NOT_CHECKED),
                $this->selectField('inspection_status', 'Inspection status', $this->optionsFromValues([
                    HousekeepingTask::INSPECTION_STATUS_PASSED,
                    HousekeepingTask::INSPECTION_STATUS_FAILED,
                ]), false, $task?->inspection_status),
                $this->textareaField('inspection_notes', 'Inspection notes', $task?->inspection_notes ?? ''),
                $this->textField('scheduled_for', 'Scheduled for', $task?->scheduled_for?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i'), 'datetime-local'),
                $this->textareaField('notes', 'Notes', $task?->notes ?? ''),
            ],
        ];
    }

    protected function preventiveScheduleForm(array $entry, ?PreventiveMaintenanceSchedule $schedule = null): array
    {
        return [
            'module' => $entry,
            'submit_label' => $schedule ? 'Save preventive schedule' : 'Create preventive schedule',
            'fields' => [
                $this->selectField('property_id', 'Property', $this->propertyOptions(), true, $schedule?->property_id),
                $this->selectField('room_id', 'Room', $this->roomOptions(), false, $schedule?->room_id),
                $this->selectField('assigned_to_user_id', 'Assign to', $this->userOptions(), false, $schedule?->assigned_to_user_id),
                $this->textField('title', 'Title', $schedule?->title ?? '', 'text', true),
                $this->textareaField('description', 'Description', $schedule?->description ?? ''),
                $this->textField('maintenance_category', 'Category', $schedule?->maintenance_category ?? 'engineering'),
                $this->textField('priority', 'Priority', $schedule?->priority ?? MaintenanceRequest::PRIORITY_MEDIUM),
                $this->textField('frequency_days', 'Frequency days', (string) ($schedule?->frequency_days ?? '30'), 'number', true),
                $this->textField('next_due_at', 'Next due', $schedule?->next_due_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i'), 'datetime-local'),
                $this->checkboxField('is_active', 'Active schedule', (bool) ($schedule?->is_active ?? false)),
                $this->textareaField('notes', 'Notes', $schedule?->notes ?? ''),
            ],
        ];
    }

    protected function selectField(string $name, string $label, array $options, bool $required = false, mixed $value = null): array
    {
        return [
            'type' => 'select',
            'name' => $name,
            'label' => $label,
            'options' => $options,
            'required' => $required,
            'value' => $value,
        ];
    }

    protected function textField(string $name, string $label, string $value = '', string $type = 'text', bool $required = false): array
    {
        return [
            'type' => $type,
            'name' => $name,
            'label' => $label,
            'value' => $value,
            'required' => $required,
        ];
    }

    protected function textareaField(string $name, string $label, string $value = ''): array
    {
        return [
            'type' => 'textarea',
            'name' => $name,
            'label' => $label,
            'value' => $value,
        ];
    }

    protected function checkboxField(string $name, string $label, bool $value = false): array
    {
        return [
            'type' => 'checkbox',
            'name' => $name,
            'label' => $label,
            'value' => $value,
        ];
    }

    protected function editableRecord(string $module, int|string $recordId): Model
    {
        $model = match ($module) {
            'properties' => Property::class,
            'rooms' => Room::class,
            'guests' => GuestProfile::class,
            'suppliers' => Supplier::class,
            'bank-accounts' => BankAccount::class,
            'inventory-items' => InventoryItem::class,
            'maintenance-requests' => MaintenanceRequest::class,
            'housekeeping-tasks' => HousekeepingTask::class,
            'preventive-maintenance-schedules' => PreventiveMaintenanceSchedule::class,
            default => throw new HttpException(404),
        };

        return $model::query()->findOrFail($recordId);
    }

    protected function recordTitle(Model $record): string
    {
        foreach (['name', 'title', 'room_number', 'invoice_number', 'bill_number', 'payment_number', 'movement_type'] as $field) {
            if (! empty($record->{$field})) {
                return (string) $record->{$field};
            }
        }

        if ($record instanceof GuestProfile) {
            return trim(($record->first_name ?? '').' '.($record->last_name ?? '')) ?: 'Guest #'.$record->getKey();
        }

        return class_basename($record).' #'.$record->getKey();
    }

    protected function propertyOptions(): array
    {
        return Property::query()->orderBy('name')->get()->map(fn (Property $property) => [
            'value' => $property->id,
            'label' => $property->name,
        ])->all();
    }

    protected function tableOptions(?string $searchPlaceholder = null, ?callable $searchCallback = null, array $filters = [], array $bulkActions = [], array $bulkUserOptions = []): array
    {
        return array_filter([
            'search' => $searchCallback ? [
                'placeholder' => $searchPlaceholder ?? 'Search records',
                'apply' => $searchCallback,
            ] : null,
            'filters' => $filters,
            'bulk_actions' => $bulkActions,
            'bulk_user_options' => $bulkUserOptions,
        ], fn (mixed $value) => $value !== null && $value !== []);
    }

    protected function filterDefinition(string $name, string $label, array $options, callable $apply): array
    {
        return compact('name', 'label', 'options', 'apply');
    }

    protected function roomOptions(): array
    {
        return Room::query()->with('property')->orderBy('room_number')->get()->map(fn (Room $room) => [
            'value' => $room->id,
            'label' => trim('Room '.$room->room_number.' · '.($room->property?->name ?? 'Property')),
        ])->all();
    }

    protected function guestOptions(): array
    {
        return GuestProfile::query()->orderBy('first_name')->orderBy('last_name')->get()->map(fn (GuestProfile $guest) => [
            'value' => $guest->id,
            'label' => trim($guest->first_name.' '.$guest->last_name),
        ])->all();
    }

    protected function reservationOptions(): array
    {
        return Reservation::query()->with(['guestProfile', 'room'])->latest('check_in_date')->take(50)->get()->map(fn (Reservation $reservation) => [
            'value' => $reservation->id,
            'label' => trim($reservation->reservation_number.' · '.($reservation->guestProfile?->first_name ?? '').' '.($reservation->guestProfile?->last_name ?? '').' · '.($reservation->room?->room_number ? 'Room '.$reservation->room->room_number : 'Room pending')),
        ])->all();
    }

    protected function folioOptions(): array
    {
        return Folio::query()->with('reservation')->latest('id')->take(50)->get()->map(fn (Folio $folio) => [
            'value' => $folio->id,
            'label' => trim($folio->folio_number.' · '.($folio->reservation?->reservation_number ?? 'Reservation pending')),
        ])->all();
    }

    protected function supplierOptions(): array
    {
        return Supplier::query()->orderBy('name')->get()->map(fn (Supplier $supplier) => [
            'value' => $supplier->id,
            'label' => $supplier->name,
        ])->all();
    }

    protected function roomTypeOptions(): array
    {
        return RoomType::query()->with('property')->orderBy('name')->get()->map(fn (RoomType $type) => [
            'value' => $type->id,
            'label' => trim($type->name.' · '.($type->property?->name ?? 'Property')),
        ])->all();
    }

    protected function ledgerAccountOptions(): array
    {
        return LedgerAccount::query()->orderBy('name')->get()->map(fn (LedgerAccount $account) => [
            'value' => $account->id,
            'label' => $account->name,
        ])->all();
    }

    protected function bankAccountOptions(): array
    {
        return BankAccount::query()->orderBy('name')->get()->map(fn (BankAccount $account) => [
            'value' => $account->id,
            'label' => $account->name,
        ])->all();
    }

    protected function purchaseOrderOptions(bool $onlyDraft = true): array
    {
        $query = PurchaseOrder::query()->with('supplier')->latest('order_date');

        if ($onlyDraft) {
            $query->whereIn('status', [PurchaseOrder::STATUS_DRAFT, PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_PARTIALLY_RECEIVED]);
        }

        return $query->take(50)->get()->map(fn (PurchaseOrder $order) => [
            'value' => $order->id,
            'label' => trim($order->purchase_order_number.' · '.($order->supplier?->name ?? 'Supplier pending')),
        ])->all();
    }

    protected function purchaseOrderLineOptions(): array
    {
        return PurchaseOrder::query()->with(['lines.item'])->latest('order_date')->take(50)->get()
            ->flatMap(fn (PurchaseOrder $order) => $order->lines->map(fn ($line) => [
                'value' => $line->id,
                'label' => trim($order->purchase_order_number.' · '.($line->item?->name ?? $line->description)),
            ]))
            ->values()
            ->all();
    }

    protected function inventoryItemOptions(): array
    {
        return InventoryItem::query()->orderBy('name')->get()->map(fn (InventoryItem $item) => [
            'value' => $item->id,
            'label' => $item->name,
        ])->all();
    }

    protected function userOptions(): array
    {
        return User::query()->orderBy('name')->get()->map(fn (User $user) => [
            'value' => $user->id,
            'label' => $user->name,
        ])->all();
    }

    protected function cashierShiftOptions(): array
    {
        return PosCashierShift::query()->latest('opened_at')->take(50)->get()->map(fn (PosCashierShift $shift) => [
            'value' => $shift->id,
            'label' => $shift->shift_number,
        ])->all();
    }

    protected function optionsFromValues(array $values): array
    {
        return collect($values)->map(fn (string $value) => [
            'value' => $value,
            'label' => str($value)->headline()->toString(),
        ])->all();
    }

    protected function storeReservation(Request $request): array
    {
        $validated = $request->validate([
            'property_id' => ['required', 'integer', Rule::exists('hotel_properties', 'id')],
            'room_id' => ['required', 'integer', Rule::exists('hotel_rooms', 'id')],
            'guest_profile_id' => ['required', 'integer', Rule::exists('hotel_guest_profiles', 'id')],
            'booking_source' => ['nullable', 'string', 'max:50'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'status' => ['required', Rule::in([Reservation::STATUS_PENDING, Reservation::STATUS_CONFIRMED])],
            'check_in_date' => ['required', 'date'],
            'check_out_date' => ['required', 'date', 'after:check_in_date'],
            'adult_count' => ['required', 'integer', 'min:1'],
            'child_count' => ['nullable', 'integer', 'min:0'],
            'rate_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'special_requests' => ['nullable', 'string'],
        ]);

        $reservation = $this->reservationService->create($validated);

        return [
            'message' => 'Reservation '.$reservation->reservation_number.' created.',
            'redirect' => route('admin.workspace.modules.show', ['module' => 'reservations']),
        ];
    }

    protected function storeProperty(Request $request): array
    {
        $validated = $request->validate([
            'branch_code' => ['required', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:255'],
            'property_type' => ['required', 'string', 'max:50'],
            'timezone' => ['required', 'string', 'max:100'],
            'currency_code' => ['required', 'string', 'size:3'],
            'check_in_time' => ['nullable', 'date_format:H:i'],
            'check_out_time' => ['nullable', 'date_format:H:i'],
            'status' => ['nullable', 'string', 'max:30'],
        ]);

        $property = Property::query()->create($validated);

        return [
            'message' => 'Property '.$property->name.' created.',
            'redirect' => route('admin.workspace.modules.show', ['module' => 'properties']),
        ];
    }

    protected function updateProperty(Property $property, Request $request): array
    {
        $validated = $request->validate([
            'branch_code' => ['required', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:255'],
            'property_type' => ['required', 'string', 'max:50'],
            'timezone' => ['required', 'string', 'max:100'],
            'currency_code' => ['required', 'string', 'size:3'],
            'check_in_time' => ['nullable', 'date_format:H:i'],
            'check_out_time' => ['nullable', 'date_format:H:i'],
            'status' => ['nullable', 'string', 'max:30'],
        ]);

        $property->update($validated);

        return [
            'message' => 'Property '.$property->name.' updated.',
            'redirect' => route('admin.workspace.records.show', ['module' => 'properties', 'record' => $property->id]),
        ];
    }

    protected function storeRoom(Request $request): array
    {
        $validated = $request->validate([
            'property_id' => ['required', 'integer', Rule::exists('hotel_properties', 'id')],
            'room_type_id' => ['required', 'integer', Rule::exists('hotel_room_types', 'id')],
            'floor_label' => ['nullable', 'string', 'max:40'],
            'room_number' => ['required', 'string', 'max:40'],
            'status' => ['nullable', 'string', 'max:30'],
            'cleaning_status' => ['nullable', 'string', 'max:30'],
            'is_smoking_allowed' => ['nullable', 'boolean'],
        ]);

        $room = Room::query()->create($validated);

        return [
            'message' => 'Room '.$room->room_number.' created.',
            'redirect' => route('admin.workspace.modules.show', ['module' => 'rooms']),
        ];
    }

    protected function updateRoom(Room $room, Request $request): array
    {
        $validated = $request->validate([
            'property_id' => ['required', 'integer', Rule::exists('hotel_properties', 'id')],
            'room_type_id' => ['required', 'integer', Rule::exists('hotel_room_types', 'id')],
            'floor_label' => ['nullable', 'string', 'max:40'],
            'room_number' => ['required', 'string', 'max:40'],
            'status' => ['nullable', 'string', 'max:30'],
            'cleaning_status' => ['nullable', 'string', 'max:30'],
            'is_smoking_allowed' => ['nullable', 'boolean'],
        ]);

        $room->update([...$validated, 'is_smoking_allowed' => $request->boolean('is_smoking_allowed')]);

        return [
            'message' => 'Room '.$room->room_number.' updated.',
            'redirect' => route('admin.workspace.records.show', ['module' => 'rooms', 'record' => $room->id]),
        ];
    }

    protected function storeGuest(Request $request): array
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'passport_number' => ['nullable', 'string', 'max:100'],
            'is_vip' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $guest = GuestProfile::query()->create($validated);

        return [
            'message' => 'Guest '.$guest->first_name.' '.$guest->last_name.' created.',
            'redirect' => route('admin.workspace.modules.show', ['module' => 'guests']),
        ];
    }

    protected function updateGuest(GuestProfile $guest, Request $request): array
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'passport_number' => ['nullable', 'string', 'max:100'],
            'is_vip' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $guest->update([...$validated, 'is_vip' => $request->boolean('is_vip')]);

        return [
            'message' => 'Guest '.$guest->first_name.' '.$guest->last_name.' updated.',
            'redirect' => route('admin.workspace.records.show', ['module' => 'guests', 'record' => $guest->id]),
        ];
    }

    protected function storeFolio(Request $request): array
    {
        $validated = $request->validate([
            'reservation_id' => ['required', 'integer', Rule::exists('hotel_reservations', 'id')],
        ]);

        $reservation = Reservation::query()->findOrFail($validated['reservation_id']);
        $folio = $this->folioService->openForReservation($reservation);

        return [
            'message' => 'Folio '.$folio->folio_number.' is ready.',
            'redirect' => route('admin.workspace.modules.show', ['module' => 'folios']),
        ];
    }

    protected function storeInvoice(Request $request): array
    {
        $validated = $request->validate([
            'folio_id' => ['required', 'integer', Rule::exists('hotel_folios', 'id')],
            'issue_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $folio = Folio::query()->findOrFail($validated['folio_id']);
        $invoice = $this->accountsReceivableService->issueInvoiceFromFolio($folio, $validated);

        return [
            'message' => 'Invoice '.$invoice->invoice_number.' issued.',
            'redirect' => route('admin.workspace.modules.show', ['module' => 'invoices']),
        ];
    }

    protected function storeSupplierBill(Request $request): array
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'integer', Rule::exists('accounting_suppliers', 'id')],
            'purchase_order_id' => ['nullable', 'integer', Rule::exists('procurement_purchase_orders', 'id')],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'bill_date' => ['required', 'date'],
            'due_date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.purchase_order_line_id' => ['nullable', 'integer', Rule::exists('procurement_purchase_order_lines', 'id')],
            'lines.*.inventory_item_id' => ['nullable', 'integer', Rule::exists('inventory_items', 'id')],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $bill = $this->accountsPayableService->createSupplierBill($validated);

        return [
            'message' => 'Supplier bill '.$bill->bill_number.' created.',
            'redirect' => route('admin.workspace.modules.show', ['module' => 'supplier-bills']),
        ];
    }

    protected function storeMaintenanceRequest(Request $request, User $user): array
    {
        $validated = $request->validate([
            'property_id' => ['required', 'integer', Rule::exists('hotel_properties', 'id')],
            'room_id' => ['nullable', 'integer', Rule::exists('hotel_rooms', 'id')],
            'assigned_to_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'maintenance_category' => ['nullable', 'string', 'max:40'],
            'priority' => ['required', 'string', 'max:20'],
            'scheduled_for' => ['nullable', 'date'],
        ]);

        $maintenanceRequest = $this->maintenanceService->create([
            ...$validated,
            'reported_by_user_id' => $user->id,
        ]);

        return [
            'message' => 'Maintenance request '.$maintenanceRequest->title.' created.',
            'redirect' => route('admin.workspace.modules.show', ['module' => 'maintenance-requests']),
        ];
    }

    protected function storeHousekeepingTask(Request $request): array
    {
        $validated = $request->validate([
            'property_id' => ['required', 'integer', Rule::exists('hotel_properties', 'id')],
            'room_id' => ['required', 'integer', Rule::exists('hotel_rooms', 'id')],
            'reservation_id' => ['nullable', 'integer', Rule::exists('hotel_reservations', 'id')],
            'assigned_to_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'task_type' => ['required', 'string', 'max:40'],
            'status' => ['nullable', 'string', 'max:30'],
            'priority' => ['nullable', 'string', 'max:20'],
            'scheduled_for' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $task = HousekeepingTask::query()->create($validated);

        return [
            'message' => 'Housekeeping task created for room '.$task->room?->room_number.'.',
            'redirect' => route('admin.workspace.modules.show', ['module' => 'housekeeping-tasks']),
        ];
    }

    protected function storePreventiveMaintenanceSchedule(Request $request): array
    {
        $validated = $request->validate([
            'property_id' => ['required', 'integer', Rule::exists('hotel_properties', 'id')],
            'room_id' => ['nullable', 'integer', Rule::exists('hotel_rooms', 'id')],
            'assigned_to_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'maintenance_category' => ['nullable', 'string', 'max:40'],
            'priority' => ['nullable', 'string', 'max:20'],
            'frequency_days' => ['required', 'integer', 'min:1', 'max:365'],
            'next_due_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $schedule = PreventiveMaintenanceSchedule::query()->create($validated);

        return [
            'message' => 'Preventive maintenance schedule '.$schedule->title.' created.',
            'redirect' => route('admin.workspace.modules.show', ['module' => 'preventive-maintenance-schedules']),
        ];
    }

    protected function storeCashierShift(Request $request): array
    {
        $validated = $request->validate([
            'property_id' => ['required', 'integer', Rule::exists('hotel_properties', 'id')],
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'opening_cash_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $shift = $this->posService->openShift($validated);

        return [
            'message' => 'Cashier shift '.$shift->shift_number.' opened.',
            'redirect' => route('admin.workspace.modules.show', ['module' => 'pos-cashier-shifts']),
        ];
    }

    protected function storePurchaseOrder(Request $request): array
    {
        $validated = $request->validate([
            'property_id' => ['nullable', 'integer', Rule::exists('hotel_properties', 'id')],
            'supplier_id' => ['required', 'integer', Rule::exists('accounting_suppliers', 'id')],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'expected_delivery_date' => ['nullable', 'date'],
            'quantity_tolerance_percent' => ['nullable', 'numeric', 'min:0'],
            'amount_tolerance_percent' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'approval_steps' => ['nullable', 'array'],
            'approval_steps.*.approver_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.inventory_item_id' => ['required', 'integer', Rule::exists('inventory_items', 'id')],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.ordered_quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $validated['approval_steps'] = collect($validated['approval_steps'] ?? [])->filter(fn (array $step) => ! empty($step['approver_user_id']))->values()->all();
        $order = $this->inventoryService->createPurchaseOrder($validated);

        return [
            'message' => 'Purchase order '.$order->purchase_order_number.' created.',
            'redirect' => route('admin.workspace.modules.show', ['module' => 'purchase-orders']),
        ];
    }

    protected function storeBankAccount(Request $request): array
    {
        $validated = $request->validate([
            'ledger_account_id' => ['nullable', 'integer', Rule::exists('accounting_ledger_accounts', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_number_last4' => ['nullable', 'string', 'max:10'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'current_balance' => ['nullable', 'numeric'],
            'is_active' => ['nullable', 'boolean'],
            'opened_at' => ['nullable', 'date'],
        ]);

        $account = BankAccount::query()->create($validated);

        return [
            'message' => 'Bank account '.$account->name.' created.',
            'redirect' => route('admin.workspace.modules.show', ['module' => 'bank-accounts']),
        ];
    }

    protected function updateBankAccount(BankAccount $account, Request $request): array
    {
        $validated = $request->validate([
            'ledger_account_id' => ['nullable', 'integer', Rule::exists('accounting_ledger_accounts', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_number_last4' => ['nullable', 'string', 'max:10'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'current_balance' => ['nullable', 'numeric'],
            'is_active' => ['nullable', 'boolean'],
            'opened_at' => ['nullable', 'date'],
        ]);

        $account->update([...$validated, 'is_active' => $request->boolean('is_active')]);

        return [
            'message' => 'Bank account '.$account->name.' updated.',
            'redirect' => route('admin.workspace.records.show', ['module' => 'bank-accounts', 'record' => $account->id]),
        ];
    }

    protected function storeBankReconciliation(Request $request): array
    {
        $validated = $request->validate([
            'bank_account_id' => ['required', 'integer', Rule::exists('accounting_bank_accounts', 'id')],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'statement_ending_balance' => ['required', 'numeric'],
            'book_ending_balance' => ['nullable', 'numeric'],
            'status' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],
        ]);

        $reconciliation = $this->bankReconciliationService->create($validated);

        return [
            'message' => 'Bank reconciliation created.',
            'redirect' => route('admin.workspace.records.show', ['module' => 'bank-reconciliations', 'record' => $reconciliation->id]),
        ];
    }

    protected function storeInventoryItem(Request $request): array
    {
        $validated = $request->validate([
            'property_id' => ['nullable', 'integer', Rule::exists('hotel_properties', 'id')],
            'preferred_supplier_id' => ['nullable', 'integer', Rule::exists('accounting_suppliers', 'id')],
            'sku' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:80'],
            'unit_of_measure' => ['nullable', 'string', 'max:30'],
            'current_quantity' => ['nullable', 'numeric'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'par_level' => ['nullable', 'numeric', 'min:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $item = InventoryItem::query()->create($validated);

        return [
            'message' => 'Inventory item '.$item->name.' created.',
            'redirect' => route('admin.workspace.modules.show', ['module' => 'inventory-items']),
        ];
    }

    protected function updateInventoryItem(InventoryItem $item, Request $request): array
    {
        $validated = $request->validate([
            'property_id' => ['nullable', 'integer', Rule::exists('hotel_properties', 'id')],
            'preferred_supplier_id' => ['nullable', 'integer', Rule::exists('accounting_suppliers', 'id')],
            'sku' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:80'],
            'unit_of_measure' => ['nullable', 'string', 'max:30'],
            'current_quantity' => ['nullable', 'numeric'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'par_level' => ['nullable', 'numeric', 'min:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $item->update([...$validated, 'is_active' => $request->boolean('is_active')]);

        return [
            'message' => 'Inventory item '.$item->name.' updated.',
            'redirect' => route('admin.workspace.records.show', ['module' => 'inventory-items', 'record' => $item->id]),
        ];
    }

    protected function updateMaintenanceRequest(MaintenanceRequest $maintenanceRequest, Request $request): array
    {
        $validated = $request->validate([
            'property_id' => ['required', 'integer', Rule::exists('hotel_properties', 'id')],
            'room_id' => ['nullable', 'integer', Rule::exists('hotel_rooms', 'id')],
            'assigned_to_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'maintenance_category' => ['nullable', 'string', 'max:40'],
            'priority' => ['required', 'string', 'max:20'],
            'status' => ['required', 'string', 'max:30'],
            'scheduled_for' => ['nullable', 'date'],
            'technician_notes' => ['nullable', 'string'],
        ]);

        $this->maintenanceService->update($maintenanceRequest, $validated);

        return [
            'message' => 'Maintenance request updated.',
            'redirect' => route('admin.workspace.records.show', ['module' => 'maintenance-requests', 'record' => $maintenanceRequest->id]),
        ];
    }

    protected function updateHousekeepingTask(HousekeepingTask $task, Request $request): array
    {
        $validated = $request->validate([
            'property_id' => ['required', 'integer', Rule::exists('hotel_properties', 'id')],
            'room_id' => ['required', 'integer', Rule::exists('hotel_rooms', 'id')],
            'reservation_id' => ['nullable', 'integer', Rule::exists('hotel_reservations', 'id')],
            'assigned_to_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'task_type' => ['required', 'string', 'max:40'],
            'status' => ['required', 'string', 'max:30'],
            'priority' => ['nullable', 'string', 'max:20'],
            'linen_status' => ['nullable', 'string', 'max:30'],
            'minibar_status' => ['nullable', 'string', 'max:30'],
            'inspection_status' => ['nullable', 'string', 'max:30'],
            'inspection_notes' => ['nullable', 'string'],
            'scheduled_for' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->housekeepingService->updateTask($task, $validated);

        return [
            'message' => 'Housekeeping task updated.',
            'redirect' => route('admin.workspace.records.show', ['module' => 'housekeeping-tasks', 'record' => $task->id]),
        ];
    }

    protected function updatePreventiveSchedule(PreventiveMaintenanceSchedule $schedule, Request $request): array
    {
        $validated = $request->validate([
            'property_id' => ['required', 'integer', Rule::exists('hotel_properties', 'id')],
            'room_id' => ['nullable', 'integer', Rule::exists('hotel_rooms', 'id')],
            'assigned_to_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'maintenance_category' => ['nullable', 'string', 'max:40'],
            'priority' => ['nullable', 'string', 'max:20'],
            'frequency_days' => ['required', 'integer', 'min:1', 'max:365'],
            'next_due_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $schedule->update([...$validated, 'is_active' => $request->boolean('is_active')]);

        return [
            'message' => 'Preventive schedule updated.',
            'redirect' => route('admin.workspace.records.show', ['module' => 'preventive-maintenance-schedules', 'record' => $schedule->id]),
        ];
    }

    protected function bulkUpdateMaintenanceRequests(array $recordIds, array $validated): array
    {
        $requests = MaintenanceRequest::query()->whereIn('id', $recordIds)->get();
        $count = 0;

        foreach ($requests as $maintenanceRequest) {
            $attributes = match ($validated['bulk_action']) {
                'mark_in_progress' => ['status' => MaintenanceRequest::STATUS_IN_PROGRESS],
                'mark_completed' => ['status' => MaintenanceRequest::STATUS_COMPLETED],
                'assign_user' => ['assigned_to_user_id' => $validated['bulk_user_id'] ?? null],
                default => throw new HttpException(422, 'Unsupported bulk action.'),
            };

            if ($validated['bulk_action'] === 'assign_user' && empty($validated['bulk_user_id'])) {
                throw new HttpException(422, 'Bulk assignee is required.');
            }

            if (! $maintenanceRequest instanceof MaintenanceRequest) {
                continue;
            }

            $this->maintenanceService->update($maintenanceRequest, $attributes);
            $count++;
        }

        return [
            'message' => $count.' maintenance request(s) updated.',
            'redirect' => route('admin.workspace.modules.show', ['module' => 'maintenance-requests']).'?'.http_build_query(request()->query()),
        ];
    }

    protected function bulkUpdateHousekeepingTasks(array $recordIds, array $validated): array
    {
        $tasks = HousekeepingTask::query()->whereIn('id', $recordIds)->get();
        $count = 0;

        foreach ($tasks as $task) {
            $attributes = match ($validated['bulk_action']) {
                'mark_in_progress' => ['status' => HousekeepingTask::STATUS_IN_PROGRESS],
                'mark_completed' => ['status' => HousekeepingTask::STATUS_COMPLETED],
                'assign_user' => ['assigned_to_user_id' => $validated['bulk_user_id'] ?? null],
                default => throw new HttpException(422, 'Unsupported bulk action.'),
            };

            if ($validated['bulk_action'] === 'assign_user' && empty($validated['bulk_user_id'])) {
                throw new HttpException(422, 'Bulk assignee is required.');
            }

            if (! $task instanceof HousekeepingTask) {
                continue;
            }

            $this->housekeepingService->updateTask($task, $attributes);
            $count++;
        }

        return [
            'message' => $count.' housekeeping task(s) updated.',
            'redirect' => route('admin.workspace.modules.show', ['module' => 'housekeeping-tasks']).'?'.http_build_query(request()->query()),
        ];
    }

    protected function bulkUpdatePreventiveSchedules(array $recordIds, array $validated): array
    {
        $schedules = PreventiveMaintenanceSchedule::query()->whereIn('id', $recordIds)->get();
        $isActive = match ($validated['bulk_action']) {
            'activate' => true,
            'deactivate' => false,
            default => throw new HttpException(422, 'Unsupported bulk action.'),
        };

        foreach ($schedules as $schedule) {
            if (! $schedule instanceof PreventiveMaintenanceSchedule) {
                continue;
            }

            $schedule->update(['is_active' => $isActive]);
        }

        return [
            'message' => $schedules->count().' preventive schedule(s) updated.',
            'redirect' => route('admin.workspace.modules.show', ['module' => 'preventive-maintenance-schedules']).'?'.http_build_query(request()->query()),
        ];
    }

    protected function storeSupplier(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'tax_identifier' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],
        ]);

        $supplier = Supplier::query()->create($validated);

        return [
            'message' => 'Supplier '.$supplier->name.' created.',
            'redirect' => route('admin.workspace.modules.show', ['module' => 'suppliers']),
        ];
    }

    protected function updateSupplier(Supplier $supplier, Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'tax_identifier' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],
        ]);

        $supplier->update($validated);

        return [
            'message' => 'Supplier '.$supplier->name.' updated.',
            'redirect' => route('admin.workspace.records.show', ['module' => 'suppliers', 'record' => $supplier->id]),
        ];
    }

    protected function storePosOrder(Request $request): array
    {
        $validated = $request->validate([
            'property_id' => ['required', 'integer', Rule::exists('hotel_properties', 'id')],
            'cashier_shift_id' => ['nullable', 'integer', Rule::exists('hotel_pos_cashier_shifts', 'id')],
            'reservation_id' => ['nullable', 'integer', Rule::exists('hotel_reservations', 'id')],
            'folio_id' => ['nullable', 'integer', Rule::exists('hotel_folios', 'id')],
            'payment_method' => ['nullable', 'string', 'max:30'],
            'service_location' => ['nullable', 'string', 'max:40'],
            'charge_to_room' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.inventory_item_id' => ['nullable', 'integer', Rule::exists('inventory_items', 'id')],
            'lines.*.item_name' => ['nullable', 'string', 'max:255'],
            'lines.*.category' => ['nullable', 'string', 'max:60'],
            'lines.*.kitchen_station' => ['nullable', 'string', 'max:60'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $order = $this->posService->createOrder($validated);

        return [
            'message' => 'POS order '.$order->order_number.' created.',
            'redirect' => route('admin.workspace.modules.show', ['module' => 'pos-orders']),
        ];
    }
}