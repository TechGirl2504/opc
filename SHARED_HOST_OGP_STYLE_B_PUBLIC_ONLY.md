# Shared hosting: “ogp-site” but **Style B** (projects in `/home/demouser/sites`, expose only `public/`)

You said you want the **organization** of `ogp-site` (a named folder under `/home/demouser/sites/`), but with the **security model of Style B**:

- Keep the full Laravel app in: `/home/demouser/sites/ogp-site/`
- Expose **only** Laravel `public/` to the web at: `/home/demouser/public_html/ogp-site`

So the web server can’t directly reach `.env`, `storage/`, `vendor/`, etc.

---

## Final layout

```text
/home/demouser/sites/
└── ogp-site/                      # full Laravel app (private)
    ├── app/
    ├── artisan
    ├── bootstrap/
    ├── composer.json
    ├── public/                    # the ONLY web-exposed folder
    ├── storage/
    └── vendor/

/home/demouser/public_html/
└── ogp-site -> /home/demouser/sites/ogp-site/public
```

URL example:
- `https://demo.gov.mw/ogp-site/`

---

## Step 1) Clone the app into `/home/demouser/sites` (server)

```bash
mkdir -p /home/demouser/sites
cd /home/demouser/sites
git clone <YOUR_REPO_URL> ogp-site
cd /home/demouser/sites/ogp-site
```

If you need a branch:

```bash
git checkout <branch-name>
```

---

## Step 2) Install PHP deps + configure `.env` (server)

```bash
cd /home/demouser/sites/ogp-site
composer install --no-dev --optimize-autoloader

cp -n .env.example .env
php artisan key:generate
nano /home/demouser/sites/ogp-site/.env
```

Writable dirs + permissions:

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

## Step 3) Expose only `public/` under `public_html` (server)

### 3.1 Backup existing web folder (if it exists)

```bash
ls -la /home/demouser/public_html | grep ogp-site || true
```

If `/home/demouser/public_html/ogp-site` exists and is a **real directory**, back it up:

```bash
if [ -d /home/demouser/public_html/ogp-site ] && [ ! -L /home/demouser/public_html/ogp-site ]; then
  mv /home/demouser/public_html/ogp-site /home/demouser/public_html/ogp-site.BAK.$(date +%F-%H%M%S)
fi
```

If it exists and is a symlink, remove it:

```bash
if [ -L /home/demouser/public_html/ogp-site ]; then
  rm /home/demouser/public_html/ogp-site
fi
```

### 3.2 Create the Style B symlink (public-only)

```bash
ln -s /home/demouser/sites/ogp-site/public /home/demouser/public_html/ogp-site
ls -la /home/demouser/public_html | grep ogp-site
```

---

## Step 4) Migrate + seed (server)

Production-safe:

```bash
cd /home/demouser/sites/ogp-site
php artisan migrate --force
php artisan db:seed --force
```

Staging/dev reset (DESTROYS data):

```bash
cd /home/demouser/sites/ogp-site
php artisan migrate:fresh --seed --force
```

---

## Notes / gotchas

### A) `public/index.php` paths

Most Laravel apps work fine with Style B because `public/index.php` already references `../vendor/autoload.php` and `../bootstrap/app.php`, which are present one directory above `public/`.

### B) If your host requires `.htaccess`

Laravel’s `public/.htaccess` (included by default) should handle rewrites on Apache.


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
