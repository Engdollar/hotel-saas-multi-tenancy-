# Usage Guide

## First Login

After installation, sign in with the Super Admin account created during setup. This first account is the global control account for the whole platform.

The Super Admin can:

- access global settings
- manage companies and tenant lifecycle state
- switch company context when reviewing tenant data
- review dashboards, reports, notifications, and activity logs
- maintain theme, export, and template configuration

## Day-One Recommended Workflow

For a new deployment, this is the recommended order of work after first login:

1. Review global settings and confirm the project title, base domain, and branding values.
2. Confirm the first company record looks correct.
3. Verify the company lifecycle state is active.
4. Review roles and permissions before adding more users.
5. Customize dashboard, template, and export settings as needed.
6. Test login for both the Super Admin and a tenant user.

## Company Lifecycle

The company lifecycle is managed from the Company Control Center.

### Statuses

- `pending`: company exists but has not been fully approved for access
- `active`: company can access the workspace normally
- `inactive`: company is suspended from normal access

### Typical Company Actions

The Super Admin can:

- create a company manually
- approve a pending company
- activate or suspend access
- move a company back to pending review
- perform bulk lifecycle updates when managing multiple companies

When a company is not active, tenant users are redirected away from protected workspace screens.

## User, Role, and Permission Management

Use the Users, Roles, and Permissions areas to control access.

### Practical Conventions

- the Super Admin role is global and should stay above tenant scope
- tenant bootstrap roles may be protected or locked for consistency
- tenant users and their role assignments should remain company-scoped
- permissions should be granted by responsibility, not by convenience

### Recommended Administration Pattern

1. Create tenant roles around operational responsibilities.
2. Assign only the permissions needed for those responsibilities.
3. Keep sensitive configuration permissions limited to company admins or the Super Admin.
4. Review role assignments regularly when staff responsibilities change.

## Settings and Branding

The settings area controls the overall look and runtime behavior of the platform.

### Common Settings Areas

- project title
- logo and favicon
- theme preset and theme mode
- tenant base domain
- dashboard studio configuration
- export templates
- email templates

### When to Update Settings

Use the settings area whenever:

- the brand identity changes
- a tenant base domain changes
- dashboard cards or charts need to be adjusted
- document headers or email templates need new placeholders or copy

## Dashboard Studio

The dashboard supports a configurable analytics experience.

### Supported Capabilities

- dynamic stat cards
- pie, line, and bar charts
- widget visibility preferences
- widget layout persistence
- drag mode for authorized users
- company-aware dashboard data

### Suggested Dashboard Workflow

1. Enable only the widgets that matter to the tenant team.
2. Arrange high-value KPI cards near the top of the screen.
3. Use charts to summarize trends rather than duplicate tables.
4. Review the dashboard as both Super Admin and tenant users to confirm scoping.

## Platform Control Center

Super Admin navigation now separates day-to-day workspace administration from platform control pages.

### Platform Workflow Areas

- Companies: tenant lifecycle management, company search, bulk lifecycle updates, and context switching
- Reports and Activity: cross-company or company-scoped audit review, date filtering, and exports
- Intelligence: platform-level risk signals, role spread, and recommended follow-up areas
- Notifications: filtered alert inbox for unread or read system updates with direct links back to related admin pages
- Support Tickets: super-admin triage queue with company, assignee, priority, category, and status filtering

### Notification Triage Pattern

- use unread filtering to focus on items that still need action
- search notification titles or messages when tracing a specific incident or job
- open the related page directly from the notification card when operational follow-up is needed
- mark notifications as read after the underlying task has been reviewed or resolved

### Support Ticket Triage Pattern

- filter by company when investigating a specific tenant issue
- filter by assignee to review workload ownership and rebalancing needs
- filter by category, priority, and status to isolate billing, operational, or configuration queues
- use the ticket detail page to update ownership, move the ticket through its lifecycle, and continue the conversation thread

## Tenant Workspace

Tenant users now land in an ERP-oriented workspace instead of the legacy platform admin shell.

### Sidebar Structure

- Front Desk: dashboard, properties, rooms, guests, reservations, folios
- Finance: invoices, payments, refunds, supplier bills, supplier payments, AR aging, AP aging, bank accounts, bank reconciliation
- Operations: housekeeping tasks, maintenance requests, preventive maintenance schedules
- POS and Inventory: cashier shifts, POS orders, inventory items, stock movements, purchase orders
- Administration: company profile and support tickets

### Dashboard Behavior

- tenant widgets focus on occupancy, arrivals, departures, housekeeping, maintenance, AP or AR exposure, procurement alerts, and POS activity
- widget visibility and layout still use the per-user dashboard preference store
- quick actions route into tenant workspace create flows for reservations, folios, supplier bills, maintenance requests, purchase orders, and POS orders

### Tenant Module Pages

- each tenant sidebar link opens a scoped web module page under the admin workspace prefix
- module pages remain company-scoped and reuse the existing domain models and services
- module tables now support inline search, scoped filters, and bulk actions for supported operational queues so teams can work through larger backlogs without leaving the page
- inventory stock movements are now visible as a dedicated read-only module so receipts, issues, adjustments, and wastage are traceable from the tenant workspace
- row links open a tenant record workspace where operational and financial documents can be reviewed without leaving the shared admin shell
- master-data records such as properties, rooms, guests, suppliers, bank accounts, and inventory items can now be edited directly from their record pages
- operational records such as maintenance requests, housekeeping tasks, and preventive maintenance schedules can now also be edited directly from their record pages
- record pages now surface related lines, approvals, payments, reconciliation rows, inspections, room moves, and other document context
- workflow actions are available directly from record pages for reservation processing, folio charges and invoicing, invoice payments or refunds, supplier bill payments, PO approval and receiving, POS kitchen and wastage actions, cashier shift close, maintenance and housekeeping updates, reconciliation completion, and preventive schedule generation
- create flows are available for properties, rooms, guests, reservations, folios, invoices, supplier bills, suppliers, bank accounts, bank reconciliations, housekeeping tasks, maintenance requests, preventive maintenance schedules, cashier shifts, inventory items, purchase orders, and POS orders

### Working Large Operational Queues

- use the module search bar to narrow lists by keyword when a request title, room number, or document reference is known
- use filters to isolate status, property, assignee, priority, or other module-specific states before triage
- use bulk actions on supported operational queues to move multiple maintenance requests, housekeeping tasks, or preventive schedules through the same workflow step in one submission
- open any row from the table to review the full record context, then use the Edit action when the document itself needs correction instead of a workflow-only update

## Authentication and Tenant Onboarding

The public registration flow creates:

- a company record
- the first company admin
- tenant default settings

This flow is useful when new organizations should onboard through a controlled public entry point.

If the company remains pending or inactive, users are redirected to the tenant access status page until the lifecycle state changes.

## Reports, Exports, and Templates

The platform supports export customization for:

- PDF headers
- Excel headers
- email templates

Use the settings screens to change selected templates, placeholders, and branding behavior.

Recommended practice:

1. finalize branding first
2. test template output with realistic sample data
3. review both PDF and spreadsheet exports after any major template change

## Multi-Company Operating Tips

When the platform is serving multiple companies:

- keep naming conventions consistent across companies
- review the active base domain before editing tenant domains
- verify company status before investigating login complaints
- avoid using the Super Admin account for everyday tenant work unless you are debugging or auditing

## Common Administrative Checks

These quick checks help confirm a healthy tenant workspace:

- dashboard loads with the expected widgets
- settings save successfully
- users appear under the correct company
- tenant URLs resolve correctly
- roles and permissions match the intended access model