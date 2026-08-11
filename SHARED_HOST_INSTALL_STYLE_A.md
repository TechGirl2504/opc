# CNMIS Shared Hosting Install — **Style A** (all backend files under `public_html`)

This is the **shared-hosting style** like your existing `ogp-site` layout: the whole Laravel backend lives under `public_html/…`.

It assumes:
- You SSH as user **`demouser`**
- Your public domain is **`https://demo.gov.mw/`**
- You have **no sudo** and cannot edit vhosts
- This repo is a **monorepo**:
  - backend = `backend/` (Laravel)
  - frontend = `frontend/` (Vite)
- You will **clone from GitHub on the server**

Target URLs:
- Frontend: `https://demo.gov.mw/cnmis/`
- Backend: `https://demo.gov.mw/cnmis-api/`
- API base: `https://demo.gov.mw/cnmis-api/api/v1`

---

## 1) Clone the repo (server)

**Run on the server (any folder):**

```bash
mkdir -p /home/demouser/repos
cd /home/demouser/repos
git clone https://github.com/Thindwa/opc.git
cd /home/demouser/repos/opc
```

Repo locations after clone:
- Backend source: `/home/demouser/repos/opc/backend`
- Frontend source: `/home/demouser/repos/opc/frontend`

---

## 2) Frontend (build + publish) (server)

### 2.1 Build

**Run on the server:**

```bash
cd /home/demouser/repos/opc/frontend
npm ci
npm run build
```

### 2.2 Publish to web folder

**Run on the server:**

```bash
mkdir -p /home/demouser/public_html/cnmis
rsync -av --delete /home/demouser/repos/opc/frontend/dist/ /home/demouser/public_html/cnmis/
```

### 2.3 Runtime API config for the frontend

**Run on the server:**

```bash
cat > /home/demouser/public_html/cnmis/env.js <<'EOF'
(function () {
  window.__ENV__ = window.__ENV__ || {};
  window.__ENV__.VITE_API_BASE_URL = "https://demo.gov.mw/cnmis-api/api/v1";
})();
EOF
```

Verify:
- Open `https://demo.gov.mw/cnmis/env.js`

### 2.4 SPA refresh support (Apache only)

If the server supports `.htaccess` rewrites, add:

```bash
cat > /home/demouser/public_html/cnmis/.htaccess <<'EOF'
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /cnmis/
  RewriteRule ^index\.html$ - [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /cnmis/index.html [L]
</IfModule>
EOF
```

---

## 3) Backend (Laravel) — Style A publish into `public_html/cnmis-api` (server)

### 3.1 Sync backend code into web folder

This copies the monorepo `backend/` directory into `public_html/cnmis-api/`.

**Run on the server:**

```bash
mkdir -p /home/demouser/public_html/cnmis-api
rsync -av --delete /home/demouser/repos/opc/backend/ /home/demouser/public_html/cnmis-api/
```

### 3.2 Install PHP deps

**Run on the server:**

```bash
cd /home/demouser/public_html/cnmis-api
composer install --no-dev --optimize-autoloader
```

### 3.3 Create `.env` + app key

**Run on the server:**

```bash
cd /home/demouser/public_html/cnmis-api
cp -n .env.example .env
php artisan key:generate
```

### 3.4 Edit `.env`

**Run on the server:**

```bash
nano /home/demouser/public_html/cnmis-api/.env
```

Set at least (example — replace DB + passwords):

```env
APP_ENV=production
APP_DEBUG=false                          # IMPORTANT: false for production security
APP_URL=https://demo.gov.mw/cnmis-api

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cnmis
DB_USERNAME=cnmis_user
DB_PASSWORD=REPLACE_ME

CORS_ALLOWED_ORIGINS=https://demo.gov.mw

BOOTSTRAP_USERS=true
BOOTSTRAP_ADMIN_PASSWORD=REPLACE_ME
BOOTSTRAP_OPC_DATA_ENTRY_PASSWORD=REPLACE_ME
BOOTSTRAP_OPC_APPROVER_PASSWORD=REPLACE_ME
BOOTSTRAP_POLICE_PASSWORD=REPLACE_ME
BOOTSTRAP_NIS_PASSWORD=REPLACE_ME
```

> **Debugging tip:** If you need to troubleshoot errors during setup, temporarily set `APP_DEBUG=true`, then **set it back to `false`** and run `php artisan config:clear` before going live.

### 3.5 Writable dirs + permissions

**Run on the server:**

```bash
cd /home/demouser/public_html/cnmis-api

# Create all writable directories
mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache

# Set permissions (try 775 first, use 777 if Apache runs as different user)
chmod -R 775 storage bootstrap/cache

# If you get "Permission denied" errors later, use:
# chmod -R 777 storage bootstrap/cache
```

### 3.6 Migrate + seed

**Production-safe (keeps data):**

```bash
cd /home/demouser/public_html/cnmis-api
php artisan migrate --force
php artisan db:seed --force
```

**Staging/dev reset (DESTROYS all data):**

```bash
cd /home/demouser/public_html/cnmis-api
php artisan migrate:fresh --seed --force
```

### 3.7 Clear caches and verify

```bash
cd /home/demouser/public_html/cnmis-api

# Clear all compiled/cached artifacts
php artisan view:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Test the health endpoint
curl -i https://demo.gov.mw/cnmis-api/up
```

**Expected output:** `HTTP/1.1 200 OK` with "Application up" message.

**If you see "Permission denied" errors:**
```bash
cd /home/demouser/public_html/cnmis-api
chmod -R 777 storage bootstrap/cache
php artisan config:clear
```

---

## 4) Verify

- Frontend: `https://demo.gov.mw/cnmis/`
- Backend health: `https://demo.gov.mw/cnmis-api/up`
- Login endpoint: `https://demo.gov.mw/cnmis-api/api/v1/auth/login`

Bootstrap users (after seeding with `BOOTSTRAP_USERS=true`):
- `admin`
- `opc_data_entry`
- `opc_approver`
- `police_officer`
- `nis_officer`

---

## 5) Troubleshooting

### 500 Error + empty `storage/logs/`

**Symptom:** `/up` returns 500, but `storage/logs/laravel.log` doesn't exist or is empty.

**Cause:** Laravel can't write to storage directories.

**Fix:**
```bash
cd /home/demouser/public_html/cnmis-api
chmod -R 777 storage bootstrap/cache
php artisan view:clear
php artisan config:clear
curl -i https://demo.gov.mw/cnmis-api/up
```

### "Permission denied" when compiling views

**Symptom:** Error mentions `storage/framework/views/...php: Failed to open stream: Permission denied`

**Fix:**
```bash
cd /home/demouser/public_html/cnmis-api
chmod -R 777 storage bootstrap/cache
php artisan view:clear
```

### Database connection errors

**Fix:**
```bash
cd /home/demouser/public_html/cnmis-api

# Verify database credentials
php artisan tinker
>>> DB::connection()->getPdo();

# If credentials are wrong, update .env and clear config:
nano .env
php artisan config:clear
```

### After updating code from git

```bash
cd /home/demouser/public_html/cnmis-api
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan view:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Re-publish frontend if changed:
cd /home/demouser/sites/cnmis/frontend
npm ci
npm run build
rsync -av --delete dist/ /home/demouser/public_html/cnmis/
```