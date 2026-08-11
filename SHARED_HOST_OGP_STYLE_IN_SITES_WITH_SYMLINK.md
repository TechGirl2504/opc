# Shared hosting: “ogp-site style” in `/home/demouser/sites` + symlink into `public_html`

This guide describes a common shared-host workflow:

- Keep the **whole Laravel project** in a “projects” directory like `/home/demouser/sites/<app>`
- Expose it under the web root by symlinking:
  - `/home/demouser/public_html/<app>` → `/home/demouser/sites/<app>`

This matches the “ogp-site style” (full Laravel tree under a single folder), but lets you organize code outside `public_html`.

> Important: This does **not** change what the web server can access. If your Apache/Nginx is configured to block `.env`, `storage/`, etc., you’ll still get “Forbidden” (good). If those protections are ever removed, exposing the whole tree could become risky. The safest model remains “only `public/` exposed”.

---

## What you will end up with

```text
/home/demouser/sites/
└── ogp-site/                # full Laravel app
    ├── app/
    ├── artisan
    ├── bootstrap/
    ├── composer.json
    ├── public/
    ├── storage/
    └── vendor/

/home/demouser/public_html/
└── ogp-site  ->  /home/demouser/sites/ogp-site    # symlink
```

URL (example):
- `https://demo.gov.mw/ogp-site/`

---

## Step 1) Clone the project into `/home/demouser/sites` (server)

**Run on the server:**

```bash
mkdir -p /home/demouser/sites
cd /home/demouser/sites

# Clone (replace URL with your actual repo)
git clone <YOUR_REPO_URL> ogp-site
cd /home/demouser/sites/ogp-site
```

If you need a specific branch:

```bash
git checkout <branch-name>
```

---

## Step 2) Link it into `public_html` (server)

### 2.1 If you already have an existing `public_html/ogp-site`

First check what it is:

```bash
ls -la /home/demouser/public_html | grep ogp-site
```

If it’s a normal folder (not a symlink), rename it as a backup:

```bash
mv /home/demouser/public_html/ogp-site /home/demouser/public_html/ogp-site.BAK.$(date +%F-%H%M%S)
```

Now create the symlink:

```bash
ln -s /home/demouser/sites/ogp-site /home/demouser/public_html/ogp-site
```

Verify:

```bash
ls -la /home/demouser/public_html | grep ogp-site
```

You should see something like:
`ogp-site -> /home/demouser/sites/ogp-site`

---

## Step 3) Install PHP dependencies (server)

**Run on the server (inside the app folder):**

```bash
cd /home/demouser/sites/ogp-site
composer install --no-dev --optimize-autoloader
```

---

## Step 4) Configure Laravel `.env` + permissions (server)

```bash
cd /home/demouser/sites/ogp-site
cp -n .env.example .env
php artisan key:generate
nano /home/demouser/sites/ogp-site/.env
```

Fix writable dirs + permissions:

```bash
cd /home/demouser/sites/ogp-site

# Create all writable directories
mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache

# Set permissions (try 775 first, use 777 if Apache runs as different user)
chmod -R 775 storage bootstrap/cache

# If you get "Permission denied" errors later, use:
# chmod -R 777 storage bootstrap/cache
```

---

## Step 5) Migrate + seed (server)

**Production-safe:**

```bash
cd /home/demouser/sites/ogp-site
php artisan migrate --force
php artisan db:seed --force
```

**Staging/dev reset (DESTROYS data):**

```bash
cd /home/demouser/sites/ogp-site
php artisan migrate:fresh --seed --force
```

---

## How this differs from the safer “public-only” model

If you want the safer model (recommended for new apps), do **not** symlink the whole project into `public_html`.
Instead, symlink only the `public/` directory:

```bash
ln -s /home/demouser/sites/<app>/public /home/demouser/public_html/<app>
```

That way the web server cannot “see” the full Laravel tree.


---

## Troubleshooting

### 500 Error + empty `storage/logs/`

**Symptom:** `/up` returns 500, but `storage/logs/laravel.log` doesn't exist or is empty.

**Cause:** Laravel can't write to storage directories.

**Fix:**
```bash
cd /home/demouser/sites/ogp-site
chmod -R 777 storage bootstrap/cache
php artisan view:clear
php artisan config:clear
curl -i https://demo.gov.mw/ogp-site/up
```

### "Permission denied" when compiling views

**Symptom:** Error mentions `storage/framework/views/...php: Failed to open stream: Permission denied`

**Fix:**
```bash
cd /home/demouser/sites/ogp-site
chmod -R 777 storage bootstrap/cache
php artisan view:clear
```

### After updating code from git

```bash
cd /home/demouser/sites/ogp-site
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan view:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```
