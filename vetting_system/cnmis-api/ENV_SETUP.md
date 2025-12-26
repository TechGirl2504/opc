# CNMIS API (.env) setup (Dev + Production)

The repo tooling blocks editing `.env` files directly here, so use this as a **copy/paste reference**.

## Development (localhost)

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# SPA origin (Vite dev/preview)
FRONTEND_URL=http://localhost:5173

# CORS: comma-separated list of allowed origins
# (You can include 5173/5174 + 4173/4174 as needed)
CORS_ALLOWED_ORIGINS=http://localhost:5173,http://localhost:5174,http://localhost:4173,http://localhost:4174

# Optional: wildcard subdomains via regex patterns (comma-separated)
# CORS_ALLOWED_ORIGINS_PATTERNS=#^https://.*\\.example\\.com$#
```

## Production (example) — keep commented until deployment

```dotenv
# APP_ENV=production
# APP_DEBUG=false
# APP_URL=https://api.cnmis.opc.gov.mw
#
# FRONTEND_URL=https://cnmis.opc.gov.mw
#
# CORS_ALLOWED_ORIGINS=https://cnmis.opc.gov.mw
# (Optional) allow subdomains:
# CORS_ALLOWED_ORIGINS_PATTERNS=#^https://.*\\.cnmis\\.opc\\.gov\\.mw$#
```


