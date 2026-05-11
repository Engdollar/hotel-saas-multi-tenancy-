# Installation Guide

## Supported Installation Paths

The project supports two standard installation paths:

1. Browser-based setup through `/install`
2. CLI-driven setup through Artisan commands

Use the browser installer when you want a guided flow. Use the CLI path when you are automating deployment or preparing a repeatable server setup.

## Before You Start

Prepare these items before installation:

- PHP version that meets the installer requirement
- MySQL database already created and reachable from the application host
- required PHP extensions enabled
- writable `.env`, `storage`, and `bootstrap/cache`
- Composer dependencies installed
- frontend assets built for production or Vite running locally during development

## Web Wizard Installation

The installer is the standard browser flow for new environments.

### Step 1: Requirements

Open `/install` and review the requirements panel. The installer checks:

- PHP runtime version
- required PHP extensions
- writable paths needed for environment and cache updates

Do not continue until the failed checks are fixed. The later steps assume the environment is already valid.

### Step 2: Application Settings

Enter the application-level settings:

- project title
- main application URL

These values are written into the environment and are later reflected in the user-facing interface.

### Step 3: Database Settings

Enter the live database connection values:

- host
- port
- database name
- username
- password

Use the `Test connection` button before continuing. This confirms only that the application can connect to MySQL with the supplied credentials. It does not create the database automatically.

### Step 4: Tenancy Settings

Define the shared base domain and bootstrap company name.

- `TENANCY_BASE_DOMAIN` should be the shared platform domain
- `default_company_name` becomes the first tenant company created during setup

The default company domain can be assigned later from company management.

### Step 5: Super Admin Account

Provide the bootstrap Super Admin information:

- administrator name
- administrator email
- administrator password

After submission, the installer writes environment values, runs the required setup work, and redirects you to the authentication flow.

## CLI Installation

Use the CLI path when setting up a server manually or automating deployment.

### Standard Sequence

1. Install backend dependencies with `composer install`.
2. Install frontend dependencies with `npm install` or `npm ci`.
3. Copy `.env.example` to `.env` if a file does not already exist.
4. Generate the application key with `php artisan key:generate`.
5. Verify runtime requirements with `php artisan install:requirements`.
6. Run database migrations with `php artisan migrate`.
7. Run the canonical bootstrap command: `php artisan system:setup`.
8. Build frontend assets with `npm run build` for production use.

### Canonical Command Note

`system:setup` is the canonical project bootstrap command.

`app:setup-admin` remains only as a compatibility alias and should not be treated as the primary documented workflow.

## Local Installation on Windows with Herd

When developing locally on Windows with Herd:

1. Keep `APP_URL` on the local `.test` domain.
2. Set `TENANCY_BASE_DOMAIN` to the same shared local domain when you need tenant subdomains.
3. Use the helper script to register a tenant host in the local hosts file.

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\register-tenant-subdomain.ps1 -Subdomain somlogic
```

4. Reopen the tenant URL after host registration.
5. If login or routing behaves oddly, clear cookies for the local domain and retry.

## VPS and Shared Hosting Notes

For VPS or hosted server environments:

1. Use the real base domain during installation.
2. Point the primary domain and tenant subdomains to the same Laravel `public` directory.
3. Confirm the database host accepts remote or local connections from the server.
4. Configure HTTPS before exposing the system publicly.
5. Run `npm run build` if assets are not already built during CI.
6. Clear caches with `php artisan optimize:clear` after configuration changes.

For the full production checklist, exact `.env` examples, and Apache or Nginx server blocks, read `deployment.md`.

## Required PHP Extensions

The installer validates the following extensions:

- `bcmath`
- `ctype`
- `curl`
- `dom`
- `fileinfo`
- `gd`
- `json`
- `mbstring`
- `openssl`
- `pdo`
- `pdo_mysql`
- `tokenizer`
- `xml`
- `zip`

If one of these is missing, install or enable it in the PHP runtime before retrying the installer.

## Writable Paths

The installer expects these paths to be writable by the PHP process:

- `.env`
- `storage`
- `bootstrap/cache`

If these are not writable, environment updates, caches, sessions, or file-based operations may fail.

## Reinstallation in Local Environments

Production environments redirect away from `/install` once the application is considered installed. Local and test environments intentionally keep the installer available for controlled retesting.

Use this local reinstall flow when needed:

1. Open `/install`
2. Enable `Run a fresh install`
3. Submit the installer again

This runs `migrate:fresh`, so it removes current database data. Use it only when wiping the local environment is acceptable.

## Post-Installation Checklist

After installation, verify the following before handing the platform to users:

- you can sign in with the Super Admin credentials
- the dashboard loads without asset errors
- the company base domain is correct
- tenant onboarding or company creation works
- email, export, and branding settings save correctly
- the production asset build is present if this is a deployed environment