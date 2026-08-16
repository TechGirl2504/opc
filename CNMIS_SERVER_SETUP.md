# CNMIS Server Setup

Non-Docker deployment runbook for the CNMIS vetting system on the current server.

## Server details

- Host user: `demouser2`
- Server: `webserver-master`
- OS: Ubuntu 22.04.5 LTS
- Web root: `~/public_html`
- PHP: 8.4.13
- Composer: installed
- Node/NPM: not installed by default, use `nvm`

## Production URLs

- Frontend: `https://demo2.gov.mw/cnmis-site/`
- Backend: `https://demo2.gov.mw/cnmis-api/`
- API base: `https://demo2.gov.mw/cnmis-api/api/v1`

## Database details

- DB host: `dbweb.boma.gov.mw`
- DB port: `3306`
- DB name: `demo_cnmis_v2_dbs`
- DB user: `demo_dbuser`
- DB password: `BtZlzhfBh287tD0`

## Important note

On this host, Apache did not expose `/cnmis-api` correctly by default. If `https://demo2.gov.mw/cnmis-api/up` returns `404`, the server admin must map `/cnmis-api` to:

```apache
/home/demouser2/public_html/cnmis/backend/public
```

If you do not have Apache control, ask the admin to add an `Alias` or equivalent vhost rule for that path.

## 1) Clone the repo

Run on the server:

```bash
cd ~/public_html
git clone https://github.com/TechGirl2504/opc.git cnmis
cd ~/public_html/cnmis
git switch main
```

Expected repo layout:

```text
~/public_html/cnmis/
├── backend/
├── frontend/
├── docker/
└── README.md
```

## 2) Install Node without sudo

Use `nvm` in your home directory:

```bash
mkdir -p "$HOME/.nvm"
curl -fsSL https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.3/install.sh | bash
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh"
nvm install 22
nvm use 22
node -v
npm -v
```

Optional shell persistence:

```bash
echo 'export NVM_DIR="$HOME/.nvm"' >> ~/.zshrc
echo '[ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh"' >> ~/.zshrc
source ~/.zshrc
```

## 3) Build the frontend

Run:

```bash
cd ~/public_html/cnmis/frontend
npm ci
npm run build
```

## 4) Publish the frontend

Copy the built frontend to the served folder:

```bash
mkdir -p ~/public_html/cnmis-site
rsync -av --delete dist/ ~/public_html/cnmis-site/
```

Create the runtime API config:

```bash
cat > ~/public_html/cnmis-site/env.js <<'EOF'
(function () {
  window.__ENV__ = window.__ENV__ || {};
  window.__ENV__.VITE_API_BASE_URL = "https://demo2.gov.mw/cnmis-api/api/v1";
})();
EOF
```

If your host requires SPA rewrites:

```bash
cat > ~/public_html/cnmis-site/.htaccess <<'EOF'
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /cnmis-site/
  RewriteRule ^index\.html$ - [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /cnmis-site/index.html [L]
</IfModule>
EOF
```

## 5) Prepare the backend

Run:

```bash
cd ~/public_html/cnmis/backend
composer install --no-dev --optimize-autoloader --no-scripts
cp -n .env.example .env
php artisan key:generate
```

Write the production `.env`:

```bash
cat > ~/public_html/cnmis/backend/.env <<'EOF'
APP_NAME=Laravel
APP_ENV=production
APP_KEY=YOUR_GENERATED_KEY_HERE
APP_DEBUG=false
APP_URL=https://demo2.gov.mw/cnmis-api
FRONTEND_URL=https://demo2.gov.mw/cnmis-site
CORS_ALLOWED_ORIGINS=https://demo2.gov.mw

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=dbweb.boma.gov.mw
DB_PORT=3306
DB_DATABASE=demo_cnmis_v2_dbs
DB_USERNAME=demo_dbuser
DB_PASSWORD=BtZlzhfBh287tD0

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=database

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

WEB_PUSH_SUBJECT=mailto:admin@example.com
WEB_PUSH_VAPID_PUBLIC_KEY=YOUR_PUBLIC_KEY
WEB_PUSH_VAPID_PRIVATE_KEY=YOUR_PRIVATE_KEY
EOF
```

## 6) Fix the push subscription migration

This host requires the `endpoint` index to be short enough for MySQL/MariaDB.

Edit:

```bash
cd ~/public_html/cnmis/backend
perl -0pi -e "s/string\\('endpoint', 2048\\)->unique\\(\\)/string('endpoint', 191)->unique()/g" database/migrations/2026_08_11_000000_create_push_subscriptions_table.php
grep -n "endpoint" database/migrations/2026_08_11_000000_create_push_subscriptions_table.php
```

The line should read:

```php
$table->string('endpoint', 191)->unique();
```

## 7) Create the database

Log in to MariaDB from the server if you have access:

```bash
mysql -h dbweb.boma.gov.mw -P 3306 -u demo_dbuser -p
```

If you have admin privileges, create the database:

```sql
CREATE DATABASE demo_cnmis_v2_dbs
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

If the database already exists, just continue.

## 8) Run migrations and seed

Run:

```bash
cd ~/public_html/cnmis/backend
php artisan migrate:fresh --seed --force
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

## 9) Fix permissions

Run:

```bash
cd ~/public_html/cnmis/backend
mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

If the host still blocks writes:

```bash
chmod -R 777 storage bootstrap/cache
```

## 10) Web exposure

Preferred setup:

- frontend served from `~/public_html/cnmis-site`
- backend served from `~/public_html/cnmis-api`

If `/cnmis-api` is not exposed by Apache, the server admin must map it to:

```apache
/home/demouser2/public_html/cnmis/backend/public
```

Without that mapping, the backend will continue to return Apache `404` before Laravel receives the request.

## 11) Verify

Test these in order:

```text
https://demo2.gov.mw/cnmis-site/
https://demo2.gov.mw/cnmis-api/up
https://demo2.gov.mw/cnmis-api/api/v1/auth/login
```

If the API path is not mapped by Apache, use the admin-provided alias or equivalent web root configuration.

## 12) Re-deploy later

After code updates:

```bash
cd ~/public_html/cnmis
git pull

cd frontend
npm ci
npm run build
rsync -av --delete dist/ ~/public_html/cnmis-site/

cd ../backend
composer install --no-dev --optimize-autoloader --no-scripts
php artisan migrate --force
php artisan optimize:clear
```

