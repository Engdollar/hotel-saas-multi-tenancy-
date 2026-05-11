# Hotel ERP Foundation

## Scope

This document defines the enterprise blueprint for turning the existing Laravel multi-tenant control platform into a hotel reservation and accounting ERP SaaS.

The current implementation added in this phase delivers:

- tenant-scoped hotel property, room type, room, guest, and reservation tables
- tenant-scoped SaaS subscription tables
- tenant-scoped accounting ledger and journal tables
- tenant-scoped folios, invoices, payments, refunds, suppliers, supplier bills, and supplier payments
- tenant-scoped room moves, housekeeping tasks, and maintenance requests
- tenant-scoped guest identity documents, visitor logs, and digital signature check-in capture
- self-service pre-arrival registration with compliance fields and OCR extraction hooks
- technician assignment, room inspections, linen tracking, minibar replenishment, and preventive maintenance schedules
- AR aging, AP aging, bank accounts, and bank reconciliation workflows
- POS cashier shifts, POS orders, and room-charge posting into folios
- repository and service layer examples for reservations and journal posting
- event-driven reservation revenue posting into accounting
- versioned `/api/v1` endpoints for hotel CRUD and AR/AP workflows
- versioned `/api/v1` operational endpoints for check-in, check-out, room moves, housekeeping, and maintenance
- focused feature tests that prove tenant isolation and booking conflict detection

The existing application stack is Laravel 12. This implementation extends that stack directly instead of introducing a second backend runtime. If a future roadmap requires separate services, the current module boundaries are suitable seams for extraction.

## Module Architecture

The target application is a modular monolith with bounded domains under `app/Domain`.

### Current domain layout

- `app/Domain/Hotel`
  - `Models`
  - `Contracts`
  - `Repositories`
  - `Services`
  - `Events`
- `app/Domain/Accounting`
  - `Models`
  - `Contracts`
  - `Repositories`
  - `Services`
  - `Listeners`
- `app/Domain/Billing`
  - `Models`

### Recommended next domains

- `app/Domain/Inventory`
- `app/Domain/Procurement`
- `app/Domain/POS`
- `app/Domain/HR`
- `app/Domain/Housekeeping`
- `app/Domain/Maintenance`
- `app/Domain/Reporting`
- `app/Domain/Notifications`

### Internal design rules

- Controllers stay thin and delegate to services.
- Services coordinate workflows and transactions.
- Repositories wrap persistence logic for aggregate roots.
- Events publish internal domain changes across modules.
- Models remain tenant-aware through `BelongsToCompany`.
- Authorization remains permission-driven through Spatie Permission.

## Tenant Architecture

Tenant isolation is company-based and already enforced by the platform.

### Isolation controls

- Runtime context is resolved by `SetCompanyContext` middleware.
- The active tenant is stored in `CurrentCompanyContext`.
- Tenant-aware models use `BelongsToCompany` to apply a global company scope.
- Queue payloads preserve tenant context through `QueueTenantContext`.
- Policies use `EnforcesCompanyBoundary` for same-company authorization checks.

### Isolation requirements for every new table

- Add a `company_id` foreign key unless the table is explicitly global.
- Add compound indexes starting with `company_id` for operational queries.
- Avoid nullable `company_id` except where global records are intentionally shared.
- Never resolve tenant records by raw ID without policy or scoped model access.
- Keep accounting, reservations, reports, and storage company-scoped.

### Global versus tenant records

- Global: subscription plans, root settings, Super Admin roles, global analytics rollups
- Tenant-scoped: properties, rooms, guests, reservations, ledger accounts, journals, invoices, reports, branches, payroll, inventory

## Database Design

### Implemented hotel tables

| Table | Purpose | Key indexes |
| --- | --- | --- |
| `hotel_properties` | Branch or property master | `company_id + branch_code`, `company_id + status` |
| `hotel_room_types` | Sellable room classes | `company_id + property_id + code` |
| `hotel_rooms` | Physical inventory | `company_id + property_id + room_number` |
| `hotel_guest_profiles` | Guest CRM and compliance | `company_id + last_name`, passport expiry |
| `hotel_reservations` | Booking ledger | `company_id + room_id + date window`, guest index |
| `hotel_folios` | Stay ledger and guest charges | `company_id + reservation_id`, `company_id + status` |
| `hotel_folio_lines` | Charge detail lines | `company_id + folio_id` |
| `hotel_room_moves` | Audit trail of in-stay room transfers | `company_id + reservation_id` |
| `hotel_housekeeping_tasks` | Cleaning and inspection work queue | `company_id + status`, `company_id + room_id` |
| `hotel_maintenance_requests` | Room and property issue tracking | `company_id + status`, `company_id + priority` |
| `hotel_guest_identity_documents` | Passport and guest ID verification records | `company_id + guest_profile_id`, `company_id + document_type` |
| `hotel_reservation_visitors` | Visitor and accompanying guest tracking | `company_id + reservation_id`, `company_id + checked_in_at` |
| `hotel_guest_document_extraction_requests` | OCR and manual extraction work queue for guest documents | `company_id + status`, `company_id + reservation_id` |
| `hotel_room_inspections` | Post-clean inspection records and room readiness evidence | `company_id + status`, `company_id + room_id` |
| `hotel_preventive_maintenance_schedules` | Recurring maintenance plans per room or property asset | `company_id + next_due_at`, `company_id + is_active` |
| `hotel_pos_cashier_shifts` | Cash drawer opening and closing control per cashier | `company_id + status`, `company_id + property_id` |
| `hotel_pos_orders` | POS sales header with optional folio posting | `company_id + status`, `company_id + cashier_shift_id` |
| `hotel_pos_order_lines` | POS sale line items | `company_id + pos_order_id` |

### Implemented SaaS tables

| Table | Purpose | Scope |
| --- | --- | --- |
| `saas_subscription_plans` | Billable commercial plans | Global |
| `saas_tenant_subscriptions` | Active tenant subscription lifecycle | Tenant |

### Implemented accounting tables

| Table | Purpose | Key indexes |
| --- | --- | --- |
| `accounting_ledger_accounts` | Chart of accounts | `company_id + code`, `company_id + type` |
| `accounting_journal_entries` | Journal headers | `company_id + entry_date`, source polymorphic index |
| `accounting_journal_entry_lines` | Journal detail lines | `company_id + ledger_account_id` |
| `accounting_invoices` | AR invoice headers | `company_id + status`, source polymorphic index |
| `accounting_invoice_lines` | AR invoice lines | `company_id + invoice_id` |
| `accounting_payments` | Customer payments | `company_id + invoice_id` |
| `accounting_refunds` | Customer refunds | `company_id + invoice_id` |
| `accounting_suppliers` | Supplier master | `company_id + status` |
| `accounting_supplier_bills` | AP bill headers | `company_id + status`, `company_id + supplier_id` |
| `accounting_supplier_bill_lines` | AP bill lines | `company_id + supplier_bill_id` |
| `accounting_supplier_payments` | Supplier settlements | `company_id + supplier_bill_id` |
| `accounting_bank_accounts` | Cash and bank account register | `company_id + is_active`, `company_id + currency_code` |
| `accounting_bank_reconciliations` | Statement-to-book reconciliation headers | `company_id + status`, `company_id + period_end` |
| `accounting_bank_reconciliation_lines` | Cleared and uncleared reconciliation entries | `company_id + is_cleared`, reference polymorphic index |

### Recommended next schema groups

1. Front desk and folios
   - `hotel_folios`
   - `hotel_folio_lines`
   - `hotel_payments`
   - `hotel_refunds`
2. Housekeeping and maintenance
   - `housekeeping_tasks`
   - `housekeeping_inspections`
   - `maintenance_requests`
   - `maintenance_work_orders`
3. POS and inventory
   - `pos_orders`
   - `pos_order_lines`
   - `inventory_items`
   - `inventory_movements`
   - `purchase_orders`
4. HR and payroll
   - `employees`
   - `attendance_entries`
   - `leave_requests`
   - `payroll_runs`
   - `payroll_lines`

## Reservation Workflow

### Implemented workflow

1. Reservation payload enters `ReservationService`.
2. `ReservationConflictService` rejects overlapping active bookings.
3. `ReservationRepository` persists the reservation.
4. Confirmed reservations dispatch `ReservationConfirmed`.
5. `PostReservationRevenueEntry` posts a journal entry.
6. Front desk teams can open folios, add charges, and issue invoices through `/api/v1`.
7. Operations teams can check guests in, move rooms, check guests out, and trigger housekeeping follow-up tasks.
8. Check-in now captures signature evidence, verified guest identity documents, and visitor logs in the same reservation workflow.
9. Guests can complete pre-arrival registration with contact, address, visa, consent, and OCR-request metadata before arrival.
10. Housekeeping teams can track linen and minibar replenishment, and supervisors can persist room inspection records.
11. Maintenance teams can assign technicians and generate preventive work from recurring maintenance schedules.
12. POS teams can open cashier shifts, sell orders, and post room-charge orders directly into guest folios.
13. Procurement teams can maintain supplier-backed inventory items, issue purchase orders, and receive goods directly into stock balances.
14. Purchase orders now move through draft-to-approved states, supplier bills can match directly to received PO lines, and POS orders can consume stocked inventory items.
15. Procurement chains now support sequenced approvers and tolerance-based three-way matching, while POS can carry modifiers, route kitchen work, void tickets, and record wastage.

### Planned workflow extensions

1. Quote or availability hold
2. Reservation confirmation
3. Deposit invoice
4. Folio posting during stay
5. Checkout settlement
6. Refund or no-show handling
7. Auto-posting for taxes, deposits, refunds, and room charges
8. Self-service pre-arrival identity and signature capture

## Accounting Workflow

### Implemented workflow

1. Confirmed reservation triggers accounting event.
2. `ReservationPostingService` resolves or creates system accounts.
3. A balanced journal entry is created with receivable and revenue lines.
4. `AccountsReceivableService` issues invoices from folios and records payments and refunds.
5. `AccountsPayableService` creates supplier bills and posts supplier payments.
6. `ReservationOperationsService` updates room occupancy state and creates post-checkout housekeeping tasks.
7. `MaintenanceService` places rooms into maintenance and returns them to dirty status when repair is completed.
8. `FinanceReportingService` exposes AR and AP aging summaries from open invoice and supplier bill balances.
9. `BankReconciliationService` stores bank account registers and cleared statement lines for reconciliation workflows.
10. `PosService` posts room-charge POS orders into folio lines so the stay ledger remains the single receivable source.
11. `InventoryService` receives supplier purchase orders into inventory movements so replenishment updates on-hand stock immediately.
12. `AccountsPayableService` can match supplier bills to purchase orders, while `PosService` records stock issues when paid POS orders consume inventory-backed items.
13. `InventoryService` now tracks approval steps and purchase-order match status, and `PosService` can send kitchen tickets, mark them ready, void cash orders with restock, and post wastage adjustments.

### Target workflow expansion

1. Reservation confirmation posts deferred revenue or AR depending on policy.
2. Check-in can recognize revenue nightly or at checkout.
3. POS room charges post folio receivables.
4. Supplier bills post AP and expense accruals.
5. Payroll posts salary expense and liabilities.
6. Bank reconciliation matches statement lines to cash journals.

## API Architecture

The platform should expose versioned APIs under `/api/v1`.

### Recommended endpoint groups

- `/api/v1/auth/*`
- `/api/v1/properties/*`
- `/api/v1/rooms/*`
- `/api/v1/guests/*`
- `/api/v1/reservations/*`
- `/api/v1/folios/*`
- `/api/v1/accounting/*`
- `/api/v1/inventory/*`
- `/api/v1/hr/*`
- `/api/v1/reports/*`

### Implemented endpoints in this phase

- `GET|POST /api/v1/properties`
- `GET|PUT|PATCH|DELETE /api/v1/properties/{property}`
- `GET|POST /api/v1/rooms`
- `GET|PUT|PATCH|DELETE /api/v1/rooms/{room}`
- `GET|POST /api/v1/guests`
- `GET|PUT|PATCH|DELETE /api/v1/guests/{guest}`
- `GET|POST /api/v1/reservations`
- `GET|PUT|PATCH|DELETE /api/v1/reservations/{reservation}`
- `GET|POST /api/v1/folios`
- `GET /api/v1/folios/{folio}`
- `POST /api/v1/folios/{folio}/charges`
- `GET|POST /api/v1/invoices`
- `GET /api/v1/invoices/{invoice}`
- `POST /api/v1/invoices/{invoice}/payments`
- `POST /api/v1/invoices/{invoice}/refunds`
- `GET|POST /api/v1/suppliers`
- `GET|PUT|PATCH|DELETE /api/v1/suppliers/{supplier}`
- `GET|POST /api/v1/supplier-bills`
- `GET /api/v1/supplier-bills/{supplierBill}`
- `POST /api/v1/supplier-bills/{supplierBill}/payments`
- `POST /api/v1/reservations/{reservation}/check-in`
- `POST /api/v1/reservations/{reservation}/check-out`
- `POST /api/v1/reservations/{reservation}/move-room`
- `POST /api/v1/reservations/{reservation}/check-in` with multipart support for signature and identity files
- `POST /api/v1/reservations/{reservation}/pre-arrival-registration` with multipart support for signature and document uploads plus OCR hook creation
- `GET|POST /api/v1/housekeeping-tasks`
- `GET|PUT|PATCH|DELETE /api/v1/housekeeping-tasks/{housekeepingTask}`
- `GET|POST /api/v1/maintenance-requests`
- `GET|PUT|PATCH|DELETE /api/v1/maintenance-requests/{maintenanceRequest}`
- `GET|POST /api/v1/preventive-maintenance-schedules`
- `GET|PUT|PATCH|DELETE /api/v1/preventive-maintenance-schedules/{preventiveMaintenanceSchedule}`
- `POST /api/v1/preventive-maintenance-schedules/{preventiveMaintenanceSchedule}/generate`
- `GET /api/v1/finance/ar-aging`
- `GET /api/v1/finance/ap-aging`
- `GET|POST /api/v1/bank-accounts`
- `GET|PUT|PATCH|DELETE /api/v1/bank-accounts/{bankAccount}`
- `GET|POST /api/v1/bank-reconciliations`
- `GET|PUT|PATCH|DELETE /api/v1/bank-reconciliations/{bankReconciliation}`
- `GET|POST /api/v1/pos-cashier-shifts`
- `GET /api/v1/pos-cashier-shifts/{posCashierShift}`
- `POST /api/v1/pos-cashier-shifts/{posCashierShift}/close`
- `GET|POST /api/v1/pos-orders`
- `GET /api/v1/pos-orders/{posOrder}`
- `POST /api/v1/pos-orders/{posOrder}/post-to-folio`

### API standards

- JWT or token-based stateless authentication for external clients
- versioned routes and resource transformers
- tenant context from authenticated user or trusted tenant headers at the edge
- request validation through Form Requests
- pagination, idempotency keys for payments, and standard error envelopes
- OpenAPI documentation generated from route and request metadata

## Security Architecture

### Implemented controls in the platform

- tenant-aware query scoping
- Spatie Permission RBAC
- activity logging
- CSRF protection for web routes
- policy-based same-company access checks
- queue tenant context propagation
- guest document OCR hook persistence for downstream review or extraction workers
- reconciliation line persistence for cleared and uncleared bank activity review

### Required next controls

1. MFA for privileged accounts
2. session inactivity timeout and device history
3. IP logging and suspicious login alerts
4. API rate limiting by tenant and user
5. signed document upload validation for IDs and passports
6. encrypted secrets, backup encryption, and per-tenant storage segmentation
7. immutable audit trail for financial postings and reversals

## UI Architecture

### Target shells

- Super Admin shell for SaaS control center
- Tenant Admin shell for hotel operations
- POS shell for touch devices
- Mobile task shell for housekeeping and maintenance

### Dashboard groups

- Executive KPI dashboard
- Front desk dashboard
- Housekeeping dashboard
- Accounting dashboard
- Revenue management dashboard
- Super Admin SaaS analytics dashboard

### Widget strategy

- tenant-configurable widgets per role
- branch or property filters
- date-driven drilldowns
- exportable charts and PDF report views

## Deployment Architecture

### Production topology

1. Nginx or Apache at the edge
2. Laravel application containers or PHP-FPM nodes
3. PostgreSQL or MySQL primary database with backups
4. Redis for cache, sessions, and queues
5. queue workers for notifications, reports, and batch posting
6. object storage for tenant documents and exports
7. monitoring for HTTP, queue, DB, storage, and billing health

### Required operational automation

- nightly encrypted backups
- queue supervision
- failed job alerting
- storage usage metering per tenant
- scheduled PDF and Excel report generation
- disaster recovery runbook

## Testing Strategy

### Implemented tests

- hotel model tenant isolation
- reservation conflict detection
- confirmed reservation accounting posting
- tenant subscription scoping
- check-in identity document, visitor, and signature capture
- pre-arrival registration with compliance fields and OCR hook creation
- housekeeping depth with room inspections and preventive maintenance schedule workflows
- finance controls with aging summaries and bank reconciliation workflows
- POS cashier shifts, paid POS orders, and folio-backed room-charge posting

### Required test layers

1. unit tests for services and policies
2. feature tests for every tenant-sensitive workflow
3. API contract tests for versioned endpoints
4. browser tests for front desk, POS, and Super Admin flows
5. financial invariants for balanced journals and no cross-tenant leakage
6. load tests for reservation calendar and dashboard queries

## Folder Structure Target

```text
app/
  Domain/
    Hotel/
    Accounting/
    Billing/
    Inventory/
    Procurement/
    POS/
    HR/
    Reporting/
  Http/
    Controllers/
    Requests/
    Middleware/
  Models/
  Policies/
  Providers/
database/
  migrations/
  seeders/
resources/
  views/
  js/
  css/
routes/
  web.php
  api.php
docs/
  hotel-erp-foundation.md
```

## Delivery Phases

### Phase 1 completed in this change set

- hotel core inventory and reservations foundation
- SaaS plan and tenant subscription foundation
- accounting chart and journal foundation
- reservation revenue auto-posting
- focused tenant isolation tests

### Phase 2 recommended next

1. self-service pre-arrival registration, OCR-assisted document extraction, and richer guest compliance workflows
2. housekeeping task boards, room inspection, linen tracking, minibar replenishment, and mobile staff views
3. technician assignment, preventive maintenance schedules, SLA tracking, and maintenance history reporting
4. branch dashboards, daily night audit reports, and scheduled operating packs

### Phase 3 recommended next

1. kitchen routing, POS item catalog, modifiers, voids, and cashier settlement reporting
2. procurement approval chains, supplier bill matching, stock issues, and POS item catalog reuse on top of inventory items, stock movements, purchase orders, and goods receipts
3. HR employee master, attendance entries, leave requests, payroll runs, deductions, and salary slips
4. BI reporting, scheduled exports, and tenant-facing analytics widgets

### Phase 4 recommended next

1. subscription billing automation, usage metering, suspensions, and invoicing
2. mobile task flows, offline support, and PWA hardening
3. MFA, login telemetry, risk scoring, and advanced monitoring
4. WhatsApp, SMS, email engines, and QR-based self-service guest journeys

## Production Guidance

- Keep tenant isolation at the model and policy layers even when adding APIs.
- Do not bypass `BelongsToCompany` for convenience in reporting code.
- Post financial entries from services or listeners, not controllers.
- Keep system-generated accounts locked and auditable.
- Add migrations in narrow domain batches to preserve rollback safety.
- Prefer additive schema changes with backfills for live tenants.