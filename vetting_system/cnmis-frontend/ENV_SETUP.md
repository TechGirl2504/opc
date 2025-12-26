# CNMIS Frontend (.env) setup (Dev + Production)

The repo tooling blocks editing `.env` files directly here, so use this as a **copy/paste reference**.

## Development (localhost)

```dotenv
# If not set, frontend falls back to http://localhost:8000/api/v1
VITE_API_BASE_URL=http://localhost:8000/api/v1
```

## Production (example) — keep commented until deployment

```dotenv
# VITE_API_BASE_URL=https://api.cnmis.opc.gov.mw/api/v1
```


