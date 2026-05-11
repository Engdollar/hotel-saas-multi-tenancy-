# Deployment Guide

## Goal

This guide is for deploying TAD$ Platform on a real VPS such as Bluehost VPS, DigitalOcean, Linode, or any Linux server where you control Apache or Nginx.

It covers:

1. a real VPS deployment checklist step by step
2. exact production `.env` values to use as a template
3. Apache and Nginx virtual host configuration for Laravel tenancy with subdomains

## Recommended Domain Strategy

Use a dedicated shared app domain for the platform, not the bare root domain.

Recommended pattern:

- platform domain: `app.yourdomain.com`
- tenant domains: `company1.app.yourdomain.com`, `company2.app.yourdomain.com`

This keeps the base domain clear and makes wildcard DNS and wildcard SSL easier to manage.

## Real VPS Deployment Checklist

### Step 1: Prepare the Server

Install or confirm the following on the VPS:

- PHP 8.2 or newer
- Composer
- MySQL or MariaDB
- Node.js and npm if assets are built on the server
- Apache or Nginx
- Git
- SSL tooling such as Certbot or your hosting provider SSL integration

Also enable the required PHP extensions checked by the installer:

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

### Step 2: Create DNS Records

Point the platform domain and tenant subdomains to the same VPS IP.

At minimum configure:

- `A` record for `app.yourdomain.com`
- wildcard `A` record for `*.app.yourdomain.com`

Without the wildcard record, tenant subdomains will not resolve.

### Step 3: Upload or Clone the Project

Place the application in a deployment path such as:

```bash
/var/www/tads-platform
```

Then pull the code:

```bash
git clone <your-repository-url> /var/www/tads-platform
cd /var/www/tads-platform
```

### Step 4: Install Dependencies

Install backend dependencies:

```bash
composer install --no-dev --optimize-autoloader
```

Install frontend dependencies and build assets if the build is done on the server:

```bash
npm ci
npm run build
```

If you build assets in CI, deploy the built files instead.

### Step 5: Create the Environment File

Copy the example file:

```bash
cp .env.example .env
```

Then update the values to match production. Use the production template in the next section.

### Step 6: Generate the Application Key

```bash
php artisan key:generate
```

### Step 7: Create the Database

Create the production database and database user first. The installer can test database access, but it does not create the database for you.

Make sure the MySQL user has permission to read, write, create tables, alter tables, and run migrations in that database.

### Step 8: Set File Permissions

Ensure the web server user can write to:

- `storage`
- `bootstrap/cache`
- `.env`

Typical Linux commands:

```bash
sudo chown -R www-data:www-data /var/www/tads-platform
sudo find /var/www/tads-platform/storage -type d -exec chmod 775 {} \;
sudo find /var/www/tads-platform/bootstrap/cache -type d -exec chmod 775 {} \;
sudo chmod 664 /var/www/tads-platform/.env
```

If your server uses `apache` or another user instead of `www-data`, replace the user and group accordingly.

### Step 9: Configure Apache or Nginx

Point the document root to the Laravel `public` directory, not the project root.

Use one of the server configurations from the later sections in this guide.

### Step 10: Configure SSL

You need HTTPS for the shared base domain and tenant subdomains.

Best option:

- a wildcard certificate for `*.app.yourdomain.com`

At minimum:

- SSL for `app.yourdomain.com`
- SSL for every tenant subdomain you plan to use

Without valid SSL on subdomains, tenant URLs may fail or show browser warnings.

### Step 11: Run the Requirements Check

```bash
php artisan install:requirements
```

Fix any missing extension, runtime, or write-permission issue before continuing.

### Step 12: Run the Installer

Open the installer in the browser:

```text
https://app.yourdomain.com/install
```

Then:

1. confirm all requirements pass
2. enter the real production URL
3. enter database credentials
4. use the database connection test button
5. enter the shared tenancy base domain
6. create the first Super Admin account

### Step 13: Post-Install Cleanup and Verification

After installation:

```bash
php artisan optimize:clear
```

Then verify:

- login works on the main platform domain
- the dashboard loads without asset errors
- a tenant subdomain resolves correctly
- company creation works
- settings save correctly
- email and export screens render without runtime errors

## Exact Production `.env` Template

Use this as the production starting point and replace the placeholder values with your real server values.

```env
APP_NAME="TAD$ Platform"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://app.yourdomain.com

TENANCY_RESOLVE_BY_DOMAIN=true
TENANCY_BASE_DOMAIN=app.yourdomain.com
TENANCY_ALLOW_CUSTOM_DOMAINS=true

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tads_platform_prod
DB_USERNAME=tads_platform_user
DB_PASSWORD=replace_with_real_password

SESSION_DRIVER=database
SESSION_COOKIE=tads_platform_session
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=.app.yourdomain.com

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_SCHEME=tls
MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_USERNAME=mailer@yourdomain.com
MAIL_PASSWORD=replace_with_real_password
MAIL_FROM_ADDRESS="no-reply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"
```

### Notes for These Values

- `APP_URL` should be the shared platform domain.
- `TENANCY_BASE_DOMAIN` should match that shared platform domain.
- `SESSION_DOMAIN` should usually be `.app.yourdomain.com` to share authentication across tenant subdomains.
- `DB_HOST` may be `localhost`, `127.0.0.1`, or a provider-specific database host.
- keep `APP_DEBUG=false` on production.

## Apache Virtual Host Example

This example assumes:

- app path: `/var/www/tads-platform`
- shared domain: `app.yourdomain.com`
- wildcard subdomains resolve to the same server

```apache
<VirtualHost *:80>
    ServerName app.yourdomain.com
    ServerAlias *.app.yourdomain.com
    DocumentRoot /var/www/tads-platform/public

    <Directory /var/www/tads-platform/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/tads-platform-error.log
    CustomLog ${APACHE_LOG_DIR}/tads-platform-access.log combined
</VirtualHost>
```

### If SSL Is Enabled in Apache

```apache
<IfModule mod_ssl.c>
<VirtualHost *:443>
    ServerName app.yourdomain.com
    ServerAlias *.app.yourdomain.com
    DocumentRoot /var/www/tads-platform/public

    <Directory /var/www/tads-platform/public>
        AllowOverride All
        Require all granted
    </Directory>

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/app.yourdomain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/app.yourdomain.com/privkey.pem

    ErrorLog ${APACHE_LOG_DIR}/tads-platform-ssl-error.log
    CustomLog ${APACHE_LOG_DIR}/tads-platform-ssl-access.log combined
</VirtualHost>
</IfModule>
```

### Apache Requirements

Make sure these modules are enabled:

- `rewrite`
- `ssl` if using HTTPS
- `headers`

## Nginx Server Block Example

This example assumes PHP-FPM is available and the PHP socket path is correct for the server.

```nginx
server {
    listen 80;
    server_name app.yourdomain.com *.app.yourdomain.com;
    root /var/www/tads-platform/public;
    index index.php index.html;

    access_log /var/log/nginx/tads-platform-access.log;
    error_log /var/log/nginx/tads-platform-error.log;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Nginx With HTTPS

```nginx
server {
    listen 443 ssl http2;
    server_name app.yourdomain.com *.app.yourdomain.com;
    root /var/www/tads-platform/public;
    index index.php index.html;

    ssl_certificate /etc/letsencrypt/live/app.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/app.yourdomain.com/privkey.pem;

    access_log /var/log/nginx/tads-platform-ssl-access.log;
    error_log /var/log/nginx/tads-platform-ssl-error.log;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## Common VPS Failure Points

If deployment fails, check these first:

- document root points to the wrong folder instead of `public`
- wildcard DNS is missing
- SSL is missing for tenant subdomains
- PHP extensions are missing
- `.env`, `storage`, or `bootstrap/cache` are not writable
- database credentials are wrong or the database does not exist yet
- `APP_URL`, `TENANCY_BASE_DOMAIN`, and `SESSION_DOMAIN` do not align

## Recommended Final Verification

After the VPS deployment is complete, test these in order:

1. main platform domain loads over HTTPS
2. `/install` loads if the app is not installed yet
3. database connection test passes
4. login works after installation
5. a tenant subdomain resolves to the application
6. settings save and persist
7. `php artisan optimize:clear` and `php artisan test` run without deployment surprises