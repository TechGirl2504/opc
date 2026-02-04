# OPC Vetting System — CI/CD + GHCR + Coolify

This repo is set up to follow `docs/project_devops_template.md`.

## Architecture

Two services:
- **Backend**: Laravel API (`backend/`)
- **Frontend**: Vue 3 + Vite SPA (`frontend/`)

## GHCR images & tags

Images:
- **Backend**: `ghcr.io/<owner>/<repo>-backend`
- **Frontend**: `ghcr.io/<owner>/<repo>-frontend`

Tags (both images):
- **`staging`**: moving tag (updated on every push to `staging`)
- **`<sha>`**: immutable tag (commit SHA)
- **`production`**: moving tag (updated on every push to `main` via retag)

## Ports & health

- **Backend internal port**: `80`
  - **health path**: `/up`
- **Frontend internal port**: `80`

## Runtime environment variables (Coolify)

### Backend (Laravel)

Set these in Coolify (do not bake `.env` into images):
- **`APP_KEY`** (required)
- **`APP_ENV`** (e.g. `production`)
- **`APP_DEBUG`** (`false` in production)
- **DB vars** (`DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, etc.)

### Frontend (no rebuild needed)

The frontend reads runtime config from `/env.js` (generated at container startup).

Set in Coolify:
- **`VITE_API_BASE_URL`**: e.g. `https://api.example.com/api/v1`

The container generates:
- `window.__ENV__.VITE_API_BASE_URL`

## Workflows

- **PR CI**: `.github/workflows/ci.yml`
  - runs backend tests, frontend typecheck/tests/build, and docker build smoke tests
- **Build & push staging**: `.github/workflows/deploy-staging.yml` (push to `staging`)
  - builds multi-arch images, pushes `:staging` and `:<sha>`, scans `:<sha>` with Trivy
- **Promote production**: `.github/workflows/promote-production.yml` (push to `main`)
  - retags `:staging` → `:production` without rebuilding

