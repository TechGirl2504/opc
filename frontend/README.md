## CNMIS Frontend (Vue 3 + Vuetify + Vite)

Frontend web app for the CNMIS vetting system.

### Requirements

- **Node.js**: `^20.19.0` or `>=22.12.0` (see `package.json` engines)
- **npm**

### First install (after cloning the repo)

From the repo root:

```bash
cd frontend
npm install
```

### Configure `.env`

Create `frontend/.env` (or copy from `.env.example` if present) and set:

```env
VITE_API_BASE_URL=http://localhost:8000/api/v1
```

### Run locally (dev)

```bash
npm run dev
```

Vite dev server usually runs at:

- `http://localhost:5173`

### Build + Preview

```bash
npm run build
npm run preview
```

Preview usually runs at:

- `http://localhost:4173` (or the configured preview port)

### PWA

The app is configured as a PWA using `vite-plugin-pwa`.

#### Generate PWA icons (required)

If you don’t have valid PNG icons in `public/`, generate them:

```bash
npm run generate:pwa-icons
```

This creates:

- `public/pwa-192x192.png`
- `public/pwa-512x512.png`
- `public/apple-touch-icon.png`

#### Install prompt (important note)

- **Chrome Android shows install only in a secure context**
  - Works on **`http://localhost`**
  - For a **LAN IP** (example `http://192.168.x.x:4174`) install prompt will **not** appear unless served over **HTTPS**

If you need to test from a phone while developing, options include:

- **Use HTTPS** for the frontend origin (recommended for LAN testing)
- **Use ADB reverse** (Android USB debugging) so the phone can access your computer’s localhost

### Troubleshooting

### CORS errors

If you see browser CORS errors calling the API:

- Confirm `VITE_API_BASE_URL` is correct
- Ensure the backend allows your frontend origin in `backend/.env` (`CORS_ALLOWED_ORIGINS`)

### PWA not updating

If the service worker is “stuck” during development/preview:

- In Chrome DevTools → Application
  - Unregister service worker
  - Clear storage
  - Reload


