# Project Overview

## Purpose

TAD$ Platform, short for Tenant Administration Dynamic Suite, is a tenant-aware administration platform built on Laravel 12. It combines a global platform control layer for the Super Admin with company-scoped workspaces for institutions, training centers, or business units that need separate access, branding, and operational control.

The project is designed to solve three problems at the same time:

1. Give the platform owner a single place to manage all tenant companies.
2. Give each company its own scoped workspace, users, settings, and dashboard state.
3. Keep installation, theme configuration, exports, and operational maintenance manageable from both the browser and CLI.

## Architecture Summary

- Backend framework: Laravel 12
- Frontend assets: Vite
- Styling and interactivity: Tailwind CSS, Alpine.js, custom JS
- Charts and visual analytics: Chart.js
- Authorization model: Spatie Permission
- Activity audit: Spatie Activitylog
- Multi-tenancy model: company-scoped records plus global Super Admin access
- Server bootstrap path: browser installer plus Artisan automation

## How the Platform Is Structured

The application separates global control from tenant workspaces.

- The Super Admin operates above tenant scope and can manage all companies.
- Each company acts as the tenant boundary for scoped users, settings, and data.
- Runtime domain resolution decides whether the request is for the base domain or a company domain.
- Tenant-aware middleware applies the active company context before protected areas are rendered.

This structure lets the platform behave like a single application while still preserving isolation between companies.

## Main Functional Areas

### 1. Installer and Bootstrap

The project includes a web installer for browser-based setup and CLI commands for automated or repeatable provisioning. Requirements validation, writable path checks, environment updates, database verification, migrations, and first Super Admin creation are part of this flow.

### 2. Authentication and Onboarding

The authentication layer supports standard login, password reset, verification, and public registration for new company onboarding. The onboarding flow creates the tenant company, the first company admin, and default tenant configuration.

### 3. Company Administration

The Super Admin manages company lifecycle states from the Company Control Center. Companies can be created, approved, activated, suspended, and returned to pending review based on operational needs.

### 4. Dashboard and Intelligence

The dashboard is configurable and supports cards, charts, metrics, and widget preferences. Authorized users can persist layout and visibility preferences, and the platform supports organization-specific dashboard behavior.

### 5. Branding, Themes, and Templates

Settings allow control over logos, favicon, theme preset, color mood, authentication visuals, export templates, and email templates. This lets each workspace feel platform-managed without looking generic.

### 6. RBAC and Auditability

Roles and permissions are managed through Spatie Permission, while activity logging records significant operational events. This provides both day-to-day control and an audit trail when reviewing changes.

## User Roles

### Super Admin

The Super Admin is the global platform owner. This role can:

- manage companies across the platform
- change global settings such as the shared tenancy base domain
- review reports, notifications, and system activity
- access installation and operational utilities
- switch context into company workspaces when needed

### Company Admin

The Company Admin is the main tenant-level operator. This role usually manages tenant users, workspace settings, content, and daily operations for a single company.

### Tenant User

Tenant users work only within their assigned company scope and receive access according to their roles and permissions.

## Key Services

- `InstallerService`: updates environment values, determines install state, and runs system bootstrap work
- `InstallerRequirementsService`: validates PHP version, PHP extensions, and writable paths
- `InstallerDatabaseService`: performs the live MySQL connectivity check from the installer
- `TenancyDomainService`: resolves the active base domain and normalizes tenant domains
- `DashboardService`: assembles dashboard metrics, charts, and widget data
- `CompanyOnboardingService`: creates company records and their bootstrap users
- `PermissionGeneratorService`: provisions default roles and permissions used by the platform
- `AdminNotificationService`: prepares admin-facing notifications and activity signals
- `AdminDataExportService`: handles structured export flows used in administration features

## Data and Access Conventions

The project follows these conventions throughout the codebase:

- `Super Admin` is the canonical global role name
- `Company` is the canonical tenant entity name
- the base domain should come from settings first and config second
- tenant user access should always remain company-scoped
- bootstrap automation should reference `system:setup` as the canonical command

## Canonical Operational Commands

- `php artisan install:requirements`
- `php artisan system:setup`
- `php artisan system:setup --refresh-passwords`
- `php artisan optimize:clear`
- `php artisan test`
- `npm run build`

## Typical Request Flow

1. A request arrives on either the base domain or a tenant domain.
2. Middleware determines whether the current host is the shared base domain.
3. If the request is for a tenant domain, the company context is resolved.
4. Authentication, company status, and permissions are evaluated.
5. The controller or service layer loads scoped data and returns the response.

## When to Read the Other Guides

- Read `installation.md` when setting up a new environment.
- Read `deployment.md` when preparing a real VPS or hosted production server.
- Read `usage.md` when training admins or onboarding a tenant team.
- Read `tenancy.md` when dealing with subdomains, context switching, or shared sessions.
- Read `operations.md` for deployment, testing, and maintenance routines.
- Read `troubleshooting.md` when the installer, tenancy, login, or frontend behavior looks wrong.