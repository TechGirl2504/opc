## CNMIS API (Laravel)

Backend API for the CNMIS vetting system.

### Requirements

- **PHP**: 8.2+
- **Composer**
- **Database**: SQLite (easy local) or MySQL/Postgres

Optional (only if you use the bundled Laravel Vite assets in this folder):

- **Node.js** + npm

### First install (after cloning the repo)

From the repo root:

```bash
cd cnmis-api
composer install
cp .env.example .env
php artisan key:generate
```

### Configure `.env`

Minimum local settings (example):

```env
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:5173
CORS_ALLOWED_ORIGINS=http://localhost:5173,http://localhost:5174,http://localhost:4173,http://localhost:4174
```

#### Database (SQLite - recommended for quick local setup)

```bash
touch database/database.sqlite
```

Then in `.env`:

```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/cnmis-api/database/database.sqlite
```

#### Database (MySQL - example)

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cnmis
DB_USERNAME=root
DB_PASSWORD=
```

### Migrate + seed

```bash
php artisan migrate --seed
```

This seeds lookup tables and initial roles/permissions (via `DatabaseSeeder`).

### Run locally

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

API base URL:

- `http://localhost:8000/api/v1`

### Notes

### Authentication

This API uses **Laravel Sanctum**. The frontend expects cookie/auth behavior per the current app configuration.

### Roles & permissions

This project uses **Spatie Laravel Permission**. Roles and permissions are managed under the **`web` guard**.

### CORS (common dev issue)

If the frontend runs on Vite dev/preview ports, ensure `CORS_ALLOWED_ORIGINS` includes:

- `http://localhost:5173`
- `http://localhost:5174`
- `http://localhost:4173`
- `http://localhost:4174`

### Troubleshooting

- **500s / missing relations**: check `storage/logs/laravel.log`
- **CORS errors**: confirm `.env` origins + that your `APP_URL`/`FRONTEND_URL` match the real origins
- **Permissions issues**: verify permissions exist under `guard_name = web` and re-run `php artisan db:seed` if needed
