# Operations Guide

## Canonical Commands

- `php artisan install:requirements`
- `php artisan system:setup`
- `php artisan system:setup --refresh-passwords`
- `php artisan optimize:clear`
- `php artisan test`
- `npm run build`

These are the primary commands you should treat as operational defaults for this project.

## Compatibility Alias

`php artisan app:setup-admin` remains available as a compatibility alias, but `system:setup` is the canonical command for project automation, documentation, and ongoing maintenance.

## Common Maintenance Tasks

### Clear Application Caches

Use this after changing environment values, routes, configuration, or asset state:

```bash
php artisan optimize:clear
```

### Re-Apply Bootstrap Credentials

Use this when the bootstrap accounts or related setup state need to be synchronized again:

```bash
php artisan system:setup --refresh-passwords
```

### Run the Requirements Check

Use this before installation, after runtime changes, or when moving the project to a new server:

```bash
php artisan install:requirements
```

### Run Backend Tests

Use this before deployment or after changes in controllers, services, requests, or middleware:

```bash
php artisan test
```

### Build Frontend Assets

Use this for production output or after significant frontend changes:

```bash
npm run build
```

## Recommended Deployment Sequence

Use the following order for a standard deployment:

1. Pull the latest code.
2. Run `composer install --no-dev --optimize-autoloader`.
3. Run `npm ci` and `npm run build` if assets are built on the server.
4. Verify `.env` values and shared domain settings.
5. Run `php artisan migrate --force`.
6. Run `php artisan system:setup --refresh-passwords` if bootstrap credentials or setup state need syncing.
7. Run `php artisan optimize:clear`.
8. Confirm the main domain and tenant domains resolve correctly.

## Routine Operator Checklist

For routine maintenance, verify these items regularly:

- queues or background work are healthy if enabled in your deployment
- login works on the base domain
- tenant domains resolve correctly
- dashboard assets load without Vite or cache errors
- new settings persist correctly
- exports still render after template updates

## Logging and Audit

The platform uses activity logging for key actions. Reports and activity views can be used to inspect operational changes, identify who changed settings, and review administrative operations over time.

When investigating unexpected behavior, check the activity trail before making manual corrections so the operational sequence stays clear.

## Platform Triage Workflow

For Super Admin operational review, this is the recommended order:

1. Check Notifications for unread alerts tied to tenant onboarding, workflow exceptions, or system updates.
2. Open Support Tickets and filter by company, assignee, or category to isolate the queue that needs action.
3. Use Reports, Activity, and Intelligence to confirm whether the issue is local to one company or part of a wider platform pattern.
4. Switch company context only when deeper tenant-scoped verification is required.

## Database and Setup Operations

When moving the platform between environments:

- confirm the target database exists first
- confirm credentials match the current environment
- rerun the requirements check after PHP changes
- use the installer connection test or CLI validation before a full bootstrap run

## Asset and Hot-Reload Notes

The application contains logic to remove a stale Vite `public/hot` file when the referenced dev server is unreachable. This helps avoid broken frontend loading after interrupted local development sessions.

If assets still look broken:

1. run `php artisan optimize:clear`
2. run `npm run build`
3. reload the page with a clean browser cache if necessary

## Recommended Pre-Release Checks

Before releasing to users, validate:

- installation requirements still pass
- key routes return expected responses
- tenant routing behaves correctly
- settings and exports save successfully
- dashboard data and charts render without console or asset errors