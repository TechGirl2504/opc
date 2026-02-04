## Universal Project DevOps Template — GH Actions + GHCR + Coolify

Use this as the **default reference template** whenever starting *any* new web project (backend only, frontend only, or full-stack).

It standardizes:
- repo structure (single repo or monorepo)
- branch model (staging → main promotion)
- Docker image conventions
- GitHub Actions CI/CD (build once, promote without rebuild)
- Coolify manual deploy (pull from GHCR)
- env var/secrets rules

This document is written with **placeholders**. Replace values in `<LIKE_THIS>`.

---

## 0) Decide the architecture (pick one)

### Option A — Single service
One container serves everything (server-rendered app or one API).

- **Pros**: simplest routing and deploy
- **Cons**: coupling; harder to scale frontend/backend independently

### Option B — Two services (recommended for teaching)
Separate containers:
- backend (API) container
- frontend (SPA/SSR) container

- **Pros**: clean separation; matches real teams
- **Cons**: requires CORS or routing rules

### Option C — Multi-service
backend + frontend + worker + scheduler + websocket + etc.

---

## 1) Repo structure template

### Single service repo

```text
<repo>/
├── Dockerfile
├── .github/workflows/
│   ├── ci.yml
│   ├── deploy-staging.yml
│   └── promote-production.yml
└── README.md
```

### Monorepo (backend + frontend)

```text
<repo>/
├── backend/
├── frontend/
├── docker/
│   ├── backend.Dockerfile
│   └── frontend.Dockerfile
├── .github/workflows/
│   ├── ci.yml
│   ├── deploy-staging.yml
│   └── promote-production.yml
└── README.md
```

---

## 2) Branching & release model (staging → main promotion)

Branches:
- `feature/<name>`: work branches
- `staging`: staging environment branch
- `main`: production environment branch

Rules:
- PR required into `staging` and `main`
- CI must pass
- Production is a **promotion PR**: `staging` → `main`

Why: you can deploy the **exact artifact** that staging tested.

---

## 3) Image naming & tagging convention (GHCR)

### Single service
- Image: `ghcr.io/<owner>/<repo>`
- Tags:
  - `:staging` (moving tag)
  - `:<sha>` (immutable tag)
  - `:production` (moving tag)

### Split services (recommended)
- Backend: `ghcr.io/<owner>/<repo>-backend`
- Frontend: `ghcr.io/<owner>/<repo>-frontend`
- Tags: same as above

---

## 4) Environment variables & secrets (source of truth)

### Guiding rule
- **GitHub**: CI/CD + build logic (workflows), no runtime secrets
- **Coolify**: runtime env vars + secrets (DB passwords, app keys, API keys)

### Required env var categories
- **runtime config**: `APP_ENV`, `NODE_ENV`, etc.
- **secrets**: `APP_KEY`, `DJANGO_SECRET_KEY`, JWT keys, etc.
- **database**: `DB_*` or connection strings
- **CORS / routing**: allowed origins (when frontend and backend have different domains)
- **frontend API base URL** (if not using same-domain routing): `VITE_API_BASE_URL` / `NEXT_PUBLIC_API_BASE_URL` / etc.

---

## 5) Docker baseline (the “always true” checklist)

Every production image should:
- listen on a single port (document it)
- log to stdout/stderr
- not include `.env` files baked into the image
- create required writable dirs (framework-specific)
- include required runtime extensions/drivers (DB drivers, etc.)

### Frontend runtime config pattern (recommended for SPAs)
If you need environment-specific frontend config **without rebuilding**:
- serve `/env.js`
- generate `/env.js` from env vars at container startup
- frontend reads `window.__ENV__`

This avoids “I set env vars in Coolify but nothing changed” problems.

---

## 6) GitHub Actions templates (CI + CD)

### 6.1 CI (PR checks)
Goal: block merges unless tests pass and images can build.

Checklist:
- install deps
- run tests
- build image(s)
- scan image(s) (Trivy)

Trivy recommended policy:
- fail on `HIGH,CRITICAL`
- ignore “unfixed” (`ignore-unfixed: true`) so you don’t get blocked by base-image CVEs with no patch yet

### 6.2 CD — Build staging images (push to `staging`)
Goal: build multi-arch images and push to GHCR.

Checklist:
- login to GHCR with `GITHUB_TOKEN`
- buildx multi-arch build (amd64/arm64)
- enable BuildKit cache (`type=gha`) for speed
- tag `:staging` and `:<sha>`
- scan the pushed `:<sha>` tags

### 6.3 CD — Promote to production without rebuild (push to `main`)
Goal: no rebuild drift. Retag `:staging` → `:production`.

Tooling:
- `docker buildx imagetools create --tag ... :production ... :staging`

---

## 7) Coolify deploy model (manual pull)

### What “manual deploy” means
GitHub pushes images to GHCR.
Coolify deploy happens when you click **Deploy** (Coolify pulls the configured tag).

### Backend service (example checklist)
- Type: Docker Image
- Image: `ghcr.io/<owner>/<repo>-backend`
- Tag: `staging` (or `production`)
- Internal port: `<BACKEND_PORT>` (common: 80, 8080, 8000, 3000)
- Set env vars
- Add post-deploy commands if needed (migrations, collectstatic, etc.)

### Frontend service (example checklist)
- Type: Docker Image
- Image: `ghcr.io/<owner>/<repo>-frontend`
- Tag: `staging` (or `production`)
- Internal port: `80` (common for Nginx)
- Set `VITE_API_BASE_URL` (or framework equivalent) if using separate domains

---

## 8) Promotion + rollback (teaching must-have)

### Promote
1. merge to `staging` → builds + pushes `:staging` + `:<sha>`
2. validate in staging
3. PR `staging` → `main` → retag `:staging` → `:production`
4. deploy production by pulling `:production`

### Rollback
Because you push immutable tags (`:<sha>`):
- deploy `:<last_good_sha>` to roll back precisely

---

## 9) Troubleshooting checklist (fast path)

When “works locally but not on Coolify”:
- **frontend**: check browser console + Network tab
  - is `/env.js` 200?
  - are API calls going to the correct domain?
  - any CORS errors?
- **backend**: check container logs
  - missing secrets (APP_KEY/SECRET_KEY)
  - DB driver missing (“could not find driver”)
  - permissions (cache/storage path errors)
- verify health endpoint (`/health`, `/up`, etc.)

---

## 10) Template “fill-in” section (copy and complete)

Project:
- **repo**: `<owner>/<repo>`
- **architecture**: `<single|split|multi>`
- **backend**:
  - image: `ghcr.io/<owner>/<repo>-backend:<tag>`
  - internal port: `<80|8080|8000|3000>`
  - health path: `</health|/up|/api/health>`
- **frontend**:
  - image: `ghcr.io/<owner>/<repo>-frontend:<tag>`
  - internal port: `80`
  - runtime env var for API: `<VITE_API_BASE_URL|NEXT_PUBLIC_API_BASE_URL|etc>`
- **database engine**: `<postgres|mysql|sqlite|mssql|mongodb>`
- **cache/queue**: `<redis|none>`

