# CI/CD Guide

This repository includes two GitHub Actions deployment workflows:

1. Laravel production workflow: [.github/workflows/deploy-laravel.yml](../.github/workflows/deploy-laravel.yml)
2. Legacy procedural PHP workflow: [.github/workflows/deploy-procedural-legacy.yml](../.github/workflows/deploy-procedural-legacy.yml)

Use the Laravel workflow for this project.

## Required GitHub Secrets

Create these repository secrets in GitHub:

- `SSH_PRIVATE_KEY`
- `REMOTE_HOST`
- `REMOTE_USER`
- `REMOTE_PORT` (optional, defaults to 22)
- `REMOTE_PATH` (legacy fallback)
- `REMOTE_APP_PATH` (recommended)
- `REMOTE_WEB_PATH` (recommended)
- `REMOTE_PHP` (optional, defaults to `php`)
- `DEPLOY_PRUNE` (optional, set to `true` only when you want deleted files pruned on server)

Runtime asset path note:

- The deploy workflow enforces `ASSET_PATH_PREFIX=public` in remote `.env`.
- This makes generated asset URLs resolve as `/public/build/*` and `/public/storage/*` on shared hosting setups where the web server serves files under `/public`.

Optional legacy secrets (only for the procedural workflow):

- `DB_HOST`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`

## Laravel Workflow Behavior

File: [.github/workflows/deploy-laravel.yml](../.github/workflows/deploy-laravel.yml)

Trigger:

- Push to `main`
- Manual run (`workflow_dispatch`)

Stages:

1. CI stage
   - Installs PHP and Node dependencies
   - Prepares `.env` for CI
   - Runs `php artisan test`
   - Builds Vite assets
   - Creates deploy artifact
2. Deploy stage
   - Downloads deploy artifact
   - Deploys via `rsync` over SSH
   - Runs remote Laravel commands:
     - `composer install --no-dev --optimize-autoloader`
     - `php artisan migrate --force`
     - `php artisan optimize:clear`
     - `php artisan config:cache`
     - `php artisan route:cache`
     - `php artisan view:cache`

Notes:

- `.env` is excluded from deployment.
- User uploads in `storage/app/public` are excluded from deletion.
- Deployment is incremental by default (only changed files are transferred).
- To mirror and prune deleted files, set `DEPLOY_PRUNE=true`.
- The workflow can bootstrap composer remotely if composer is not installed globally.
- The workflow publishes Laravel `public/` into `REMOTE_WEB_PATH` and rewrites `index.php` to point to `REMOTE_APP_PATH`.
- The workflow sets `ASSET_PATH_PREFIX=public` before Laravel cache rebuild commands.

### Bluehost Recommended Mapping

If `app.yourdomain.com` shows `Index of /`, your subdomain document root is pointing at the wrong folder.

Use this split setup:

- `REMOTE_APP_PATH`: full Laravel app path (example: `/home/USER/laravel-app`)
- `REMOTE_WEB_PATH`: subdomain document root (example: `/home/USER/app.somlogic.com`)

With that mapping, `app.somlogic.com` serves the correct Laravel public entrypoint while app code stays outside the web root.

## Procedural Workflow Behavior (Legacy)

File: [.github/workflows/deploy-procedural-legacy.yml](../.github/workflows/deploy-procedural-legacy.yml)

Trigger:

- Manual run only (`workflow_dispatch`)

Behavior:

- Applies optional runtime DB config patching for known procedural files if they exist
- Deploys via `rsync`

Use this only for legacy procedural projects. It is included because you asked to keep a pre-tried procedural deployment pattern.

## Recommended Production Practice

For Laravel production:

1. Keep real database and app secrets in remote `.env` only.
2. Do not commit environment-specific credentials.
3. Ensure remote web user has write permission to `storage` and `bootstrap/cache`.
4. Run first deployment from `Actions -> Laravel CI/CD to Bluehost` and check logs end to end.
