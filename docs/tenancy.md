# Tenancy Guide

## Tenancy Model

This project uses company-based tenancy.

- `Company` is the tenant entity
- most tenant data is company-scoped
- the Super Admin can bypass tenant scoping or switch into a selected company context

The tenancy model is designed to keep tenant data separated without requiring separate deployments for each company.

## Domain Resolution

Runtime domain behavior is handled by `TenancyDomainService`.

The service is responsible for:

- reading the shared base domain from settings first
- falling back to environment configuration when needed
- normalizing plain subdomains into full tenant hosts
- identifying when the current host is the base domain instead of a tenant domain
- supporting installer, settings, onboarding, and company management flows with the same rules

This keeps domain handling consistent across the application instead of scattering domain logic into multiple controllers.

## Base Domain Behavior

The shared base domain can be managed from the settings UI by the Super Admin.

Examples:

- local: `eelo-university.test`
- VPS: `app.example.com`

When a base domain exists, a company can be created with only a subdomain like `somlogic`, which becomes `somlogic.eelo-university.test` or `somlogic.app.example.com`.

### Why This Matters

This lets administrators enter simpler values while the platform stores and uses normalized tenant domains consistently.

## Tenant Domain Entry Rules

In company management and profile forms, users can generally provide:

- a plain subdomain such as `somlogic`
- a full host when operationally necessary

The platform normalizes the entered value against the active base domain so tenants behave consistently across local and deployed environments.

## Super Admin Context Switching

The Super Admin can:

- view the platform globally
- switch into a selected company context
- return to the global all-companies view

This affects tenant-scoped reads for settings, dashboard data, reports, and other scoped resources.

Use global mode when reviewing platform-wide health. Switch into a company context only when you need to inspect tenant-specific behavior.

## Local Subdomain Testing on Windows

Windows does not resolve wildcard subdomains by default for local `.test` hosts.

Use the helper script:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\register-tenant-subdomain.ps1 -Subdomain somlogic
```

After running the script:

1. retry the tenant URL
2. clear stale cookies if needed
3. confirm the base domain and tenant host are aligned

## Session and Cookie Notes

If tenant and main-domain sessions need to be shared, the session domain should match the shared base domain.

Example:

- `SESSION_DOMAIN=.eelo-university.test`

If stale cookies interfere with authentication, rotating the session cookie name can help, especially in local development where old cookies from previous domain strategies may still exist.

## Middleware Expectations

The tenancy middleware should:

- skip the shared base domain
- resolve active company domains only
- avoid treating the main platform domain as a tenant host
- guard against missing company state during bootstrap or migration phases

When these expectations are not met, the most visible symptoms are login loops, tenant misrouting, or main-domain requests behaving like tenant requests.

## Tenant Access Status

If a tenant company is not active, company users are redirected to the access status page instead of the dashboard.

This prevents inactive or pending tenants from entering protected areas until the company lifecycle status changes.

## Recommended Tenancy Checks

When debugging a tenant issue, check these items in order:

1. confirm the company status is active
2. confirm the current host matches the expected tenant or base domain
3. confirm the configured base domain is correct
4. confirm the session domain is aligned
5. clear cookies and retry with a clean session