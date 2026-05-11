# Hotel SaaS

Hotel SaaS is a Laravel 12 multi-tenant hotel reservation, operations, accounting, procurement, POS, and administration platform. It combines a global platform control center for the Super Admin with company-scoped tenant workspaces for day-to-day hotel operations.

The project is built as a modular Laravel application with tenant-aware domain resolution, role-based access control, configurable dashboards, branding and template management, browser-based installation, and an ERP-style workspace for hotel teams.

## Highlights

- Multi-tenant architecture with company-scoped data isolation
- Super Admin platform control center for companies, reports, notifications, activity, and support triage
- Tenant ERP workspace for front desk, finance, operations, POS, inventory, and administration
- Laravel 12 backend with service-oriented domain workflows
- Role-based access control powered by Spatie Permission
- Activity logging powered by Spatie Activitylog
- Configurable dashboards, widget layouts, charts, and saved user preferences
- Tenant-aware themes, branding, templates, and authentication visuals
- PDF and Excel export support
- Web installer plus CLI bootstrap commands

## Main Functional Areas

### Platform Management

- company lifecycle management with pending, active, and inactive states
- global settings, base domain control, branding, templates, and dashboard configuration
- super-admin notifications and support-ticket triage
- audit and activity review across the platform

### Hotel ERP Workspace

- properties, room types, rooms, and guest profiles
- reservations, folios, invoices, payments, and refunds
- supplier bills, supplier payments, bank accounts, and bank reconciliation
- housekeeping tasks, maintenance requests, and preventive maintenance schedules
- cashier shifts, POS orders, inventory items, stock movements, and purchase orders
- tenant module pages with search, filters, bulk actions, record detail views, and edit flows

### Accounting and Finance

- journal entries and chart-of-accounts foundations
- accounts receivable and accounts payable flows
- AR aging and AP aging visibility
- reservation-driven accounting events and posting hooks
- bank reconciliation workflows

### Onboarding and Access

- public company onboarding flow
- tenant access-status gate for pending or inactive companies
- super-admin and tenant user authentication
- company-scoped roles and permissions

## Architecture Overview

The application is structured as a modular monolith.

- Backend: Laravel 12, PHP 8.2+
- Frontend build: Vite
- Styling: Tailwind CSS
- Client-side behavior: Alpine.js, custom JavaScript
- Charts: Chart.js
- Authorization: Spatie Permission
- Audit logging: Spatie Activitylog
- Exports: DomPDF and Laravel Excel

### Tenant Model

- `Company` is the tenant boundary
- `Super Admin` is the global control role
- tenant-aware models use company scoping
- runtime context is resolved by domain and request context
- queues preserve tenant context during background processing

### Design Approach

- controllers stay thin
- services coordinate workflows and transactions
- repositories wrap aggregate persistence where needed
- events connect internal accounting and reservation flows
- policies enforce company boundaries and permission checks

## Tech Stack

### Backend

- PHP 8.2+
- Laravel 12
- MySQL
- Laravel Tinker
- PHPUnit 11

### Packages

- `spatie/laravel-permission`
- `spatie/laravel-activitylog`
- `barryvdh/laravel-dompdf`
- `maatwebsite/excel`
- `yajra/laravel-datatables-oracle`

### Frontend

- Vite
- Tailwind CSS
- Alpine.js
- Axios
- Chart.js
- SweetAlert2

## Key Workflows

### Super Admin

1. Install or bootstrap the system.
2. Sign in as Super Admin.
3. Configure the base domain, branding, and global settings.
4. Review companies and activate approved tenants.
5. Monitor reports, notifications, activity, and support queues.

### Tenant Team

1. Sign in to the company workspace.
2. Use the tenant ERP dashboard for operational KPIs.
3. Manage reservations, folios, payments, and supplier workflows.
4. Process housekeeping, maintenance, procurement, POS, and stock activity.
5. Use record pages for workflow actions, edits, and document review.

## Installation

The project supports both browser-based and CLI-based installation.

### Option 1: Web Installer

1. Install dependencies:
   - `composer install`
   - `npm install`
2. Copy `.env.example` to `.env`.
3. Configure database credentials and application URL.
4. Run `php artisan key:generate`.
5. Open `/install` in the browser.
6. Complete requirements, application, database, tenancy, and Super Admin setup.

### Option 2: CLI Setup

1. Install dependencies:
   - `composer install`
   - `npm install`
2. Copy `.env.example` to `.env`.
3. Run `php artisan key:generate`.
4. Run `php artisan install:requirements`.
5. Run `php artisan migrate`.
6. Run `php artisan system:setup`.
7. Build assets with `npm run build`.

### Canonical Bootstrap Commands

- `php artisan install:requirements`
- `php artisan system:setup`
- `php artisan system:setup --refresh-passwords`
- `php artisan optimize:clear`

## Local Development

### Standard Local Workflow

1. Install dependencies:
   - `composer install`
   - `npm install`
2. Prepare environment:
   - copy `.env.example` to `.env`
   - configure database values
   - run `php artisan key:generate`
3. Start services:
   - `php artisan serve`
   - `npm run dev`
4. Run migrations:
   - `php artisan migrate`
5. Open `/install` or use the CLI bootstrap flow.

### Windows Herd and Local Tenant Domains

If you use Herd and `.test` domains on Windows, tenant hosts may need to be added manually.

Use:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\register-tenant-subdomain.ps1 -Subdomain somlogic
```

If local login behaves unexpectedly on localhost or `127.0.0.1`, confirm the session cookie and tenancy domain configuration in `.env` before debugging deeper request flow issues.

## Testing

### Backend Tests

- run the full test suite with `php artisan test`
- run Composer test script with `composer test`
- use focused test files during development when narrowing failures

### Recommended Validation

- `php artisan test`
- `php artisan optimize:clear`
- `npm run build`

### What Is Covered

- feature and unit tests for platform flows
- tenant isolation checks
- hotel workflow coverage for reservations, finance, procurement, POS, and workspace UI surfaces
- authentication and onboarding flows

## Deployment Notes

- set a correct `APP_URL`
- configure the production base domain and tenant domain strategy
- provide valid database, queue, cache, mail, and filesystem configuration
- run `npm run build` for production assets
- clear caches after configuration changes with `php artisan optimize:clear`
- point the main domain and tenant domains to Laravel's `public` directory

For a fuller production checklist, see `docs/deployment.md`.

## Documentation

- [Project overview](docs/overview.md)
- [Hotel ERP foundation](docs/hotel-erp-foundation.md)
- [Installation guide](docs/installation.md)
- [Deployment guide](docs/deployment.md)
- [CI/CD guide](docs/ci-cd.md)
- [Usage guide](docs/usage.md)
- [Tenancy guide](docs/tenancy.md)
- [Operations guide](docs/operations.md)
- [Troubleshooting guide](docs/troubleshooting.md)

## Project Structure

```text
app/
  Domain/        Domain models, services, repositories, listeners, and contracts
  Http/          Controllers, middleware, and requests
  Models/        Core application and platform models
  Policies/      Authorization policies
  Providers/     Service providers and application bootstrap
  Services/      Cross-module application services
config/          Framework and application configuration
database/        Migrations, factories, and seeders
docs/            Project documentation
resources/       Blade views, CSS, and JavaScript assets
routes/          Web, auth, and console routes
tests/           Feature and unit tests
```

## Recommended Use Cases

- hotel groups running multiple company or property workspaces
- operators needing both platform administration and tenant ERP functions
- SaaS products that need tenant-aware branding, onboarding, and role control
- teams that want Laravel-based hotel operations and accounting in one codebase

## Notes

- `system:setup` is the canonical bootstrap command for this project.
- `app:setup-admin` exists as a compatibility alias, not the preferred documented workflow.
- the platform is designed around company-scoped tenancy, not separate databases per tenant.

## License

This repository uses the Laravel application skeleton as its foundation. Review your own distribution, commercial, and project-specific licensing requirements before public distribution or resale.
