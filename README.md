# TAD$ Platform

TAD$ Platform, short for Tenant Administration Dynamic Suite, is a Laravel 12 multi-tenant administration platform with RBAC, tenant-aware branding, dashboard customization, document exports, and a web installation wizard.

## Core Capabilities

- Multi-tenant company workspaces with domain or subdomain routing
- Super Admin control center for platform-wide management
- Role-Based Access Control powered by Spatie Permission
- Hotel ERP foundation for properties, rooms, guests, reservations, subscriptions, and accounting journals
- Dynamic dashboard cards, charts, widget layout, and drag preferences
- Tenant-aware branding, themes, templates, and auth visuals
- PDF, Excel, and email template customization
- Web installer with PHP requirements checks and database connection testing

## Terminology

- Super Admin: the global platform owner role
- Company: a tenant record in the platform
- Workspace: the tenant-facing experience for a company
- Base domain: the shared root domain used to build tenant subdomains

## Standard Entry Points

- Web installer: `/install`
- Canonical setup command: `php artisan system:setup`
- Legacy compatibility alias: `php artisan app:setup-admin`
- Requirements check: `php artisan install:requirements`

## Quick Start

1. Install PHP and Node dependencies.
2. Copy `.env.example` to `.env` and adjust the database values.
3. Run `php artisan key:generate`.
4. Open `/install` in the browser for the guided setup flow.
5. If you prefer CLI setup, run `php artisan migrate` and `php artisan system:setup`.

## Documentation Index

- [Project overview](docs/overview.md)
- [Hotel ERP foundation](docs/hotel-erp-foundation.md)
- [Installation guide](docs/installation.md)
- [Deployment guide](docs/deployment.md)
- [CI/CD guide](docs/ci-cd.md)
- [Usage guide](docs/usage.md)
- [Tenancy guide](docs/tenancy.md)
- [Operations guide](docs/operations.md)
- [Troubleshooting guide](docs/troubleshooting.md)

## Local Development

1. Install dependencies:
   - `composer install`
   - `npm install`
2. Build or run assets:
   - `npm run dev`
   - or `npm run build`
3. Start Laravel through Herd or your preferred PHP server.
4. Open the installer at `/install`.

For Windows with Herd and `.test` domains, tenant subdomains must be mapped explicitly unless you use wildcard local DNS tooling. The helper script is:

`powershell -ExecutionPolicy Bypass -File .\scripts\register-tenant-subdomain.ps1 -Subdomain somlogic`

## Recommended Commands

- `php artisan install:requirements`
- `php artisan migrate`
- `php artisan system:setup --refresh-passwords`
- `php artisan optimize:clear`
- `php artisan test`
- `npm run build`

## Testing

- Run backend tests with `php artisan test`
- Use focused slices during development when possible
- Rebuild frontend assets after major UI changes with `npm run build`

## Deployment Notes

- Configure the correct `APP_URL`, database credentials, mail settings, and base domain before production use.
- Keep `system:setup` as the canonical bootstrap command in scripts and documentation.
- Use the Super Admin settings page to manage the shared base domain after installation.
- Use [docs/deployment.md](docs/deployment.md) for a full VPS checklist, production `.env` examples, and Apache or Nginx virtual host configuration.

## License

This project inherits the Laravel framework license for framework components. Review your project-specific licensing requirements separately if you distribute the application.
"# hotel-saas-multi-tenancy-" 
