# Troubleshooting Guide

## Installer Redirects to Login

### Cause

The current database already contains users, so the application is considered installed.

### Resolution

- in local environments, `/install` remains available for retesting
- use `Run a fresh install` if you want to wipe and reinstall locally
- avoid using the reinstall flow on production data

## Requirements Step Shows Failures

Check these items first:

- PHP version
- missing PHP extensions
- `.env` writability
- `storage` writability
- `bootstrap/cache` writability

Resolve the failed item first, then reload `/install`.

If the same requirement still fails after you believe it is fixed, restart the PHP runtime or web server process so the installer sees the updated environment.

## Database Test Fails

Verify:

- host
- port
- database name
- username
- password
- that MySQL accepts connections from the application host
- that the database already exists

The installer database test validates live connectivity only. It does not create the database for you.

If the error is a CSRF token mismatch in the browser, reload the installer page and retry. The request should include the installer form token.

## Tenant Subdomain Does Not Resolve Locally

On Windows local development, wildcard subdomains usually do not resolve automatically.

Use:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\register-tenant-subdomain.ps1 -Subdomain somlogic
```

Then:

1. retry the tenant URL
2. confirm the configured base domain
3. clear cookies if the browser keeps an older host session

## Login Refreshes Back to Login

Possible causes:

- stale cookies from an older session domain or cookie name
- wrong base domain behavior
- tenant company is inactive or pending
- a request is being resolved into the wrong company context

Try:

- clearing browser cookies for the local or shared domain
- confirming `SESSION_DOMAIN`
- confirming the company status is active
- verifying the base domain is not being treated as a tenant domain

## Main Domain Behaves Like a Tenant Domain

The configured base domain should not be resolved as a tenant host. The tenancy middleware should skip the shared base domain and only resolve active company domains.

If this breaks, common symptoms are:

- the main domain redirects incorrectly
- login loops occur
- settings appear to load inside the wrong tenant context

## Installer Is Available but Looks Stale

If the installer page does not reflect recent frontend changes:

1. run `php artisan optimize:clear`
2. rebuild assets with `npm run build`
3. reload the page with a hard refresh

## Widget Preferences Fail to Save

If widget preferences fail after schema changes, confirm that the expected columns exist in `user_dashboard_preferences` and rerun migrations.

Also verify the authenticated user still has access to the dashboard preference route after role or permission updates.

## Charts or Frontend Assets Look Broken

Try:

- `php artisan optimize:clear`
- `npm run build`

If a stale Vite hot file exists from an interrupted dev session, the application should clean it automatically, but clearing and rebuilding is still a safe fallback.

## When to Escalate Beyond Basic Checks

Move beyond surface checks when:

- login is correct on one domain but wrong on another
- the installer passes requirements but migrations or bootstrap logic fail
- tenant context appears inconsistent between pages
- settings save but the runtime behavior does not reflect the new values

At that point, review the affected controller, middleware, service, and environment values together instead of retrying random browser actions.