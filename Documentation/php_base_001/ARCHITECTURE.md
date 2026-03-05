# php_base_001 — Architecture

## Overview

**php_base_001** is the Reign of Play **company PHP backend**. It serves the Dutch dashboard and other RoP apps. Auth is **local**: register and login create/verify users in the dashboard DB and issue JWTs; there is no proxy to a separate “main” app for auth. Python is used only for specific business actions (e.g. create-tournaments) via a service key.

**Unified backend, multiple frontends:** One PHP backend and one MariaDB database (dutch_dashboard) back all RoP web frontends: the **dashboard** (00_dashboard), **Dutch.mt** (dutch_mt), and the **main site** (html_js_css_base_001 — static only). See [README.md#unified-backend-multiple-frontends](README.md#unified-backend-multiple-frontends) and [../dashboard/](../dashboard/README.md), [../html_js_css_base_001/](../html_js_css_base_001/README.md), [../dutch_mt/](../dutch_mt/README.md).

## Components

| Component | Role |
|-----------|------|
| **PHP (Apache)** | Serves `/api/*` endpoints, static dashboard HTML/JS/CSS (when run in Docker). Verifies JWT locally; does not call Python for auth. |
| **MariaDB** | Database `dutch_dashboard`: users, audit_log, cache, sessions, schema_migrations. Used by PHP for register/login and app data. |
| **Python API** | Optional outbound calls from PHP (e.g. create-tournament, health). PHP uses `X-Service-Key` (service-to-service); no auth proxy. |

## Request flow

1. **User → Frontend** (e.g. dashboard or dutch.mt register page) → HTTP to PHP (same-origin or cross-origin to dashboard.reignofplay.com).
2. **Frontend → PHP**  
   - **Public endpoints** (register, login, refresh, health, dutch_mt/register_for_tournament): no `Authorization` header.  
   - **Protected endpoints** (create-tournament, health-python): `Authorization: Bearer <JWT>`; PHP verifies JWT with `JWT_SECRET`.
3. **PHP → DB**  
   - PDO/MySQL to `dutch_dashboard` for user lookups, registration, sessions (as used).
4. **PHP → Python** (when needed)  
   - Outbound HTTP to `PYTHON_API_BASE_URL` with `X-Service-Key: DUTCH_MT_DASHBOARD_SERVICE_KEY` for service endpoints (e.g. create-tournaments, health). No `/service/auth/validate` call.

## Auth model

- **JWT creation:** At login, PHP creates access and refresh tokens (HS256, `JWT_SECRET`), stores user_id/username/email/role in payload.
- **JWT verification:** Protected endpoints call `require_jwt($config)` (from `lib/jwt.php`); invalid or expired token → 401.
- **No proxy:** PHP does not call the main app or Python to validate tokens; verification is local only.

## Database

- **DB name:** `dutch_dashboard`.
- **Schema:** Created by `sql/init.sql` (users, audit_log, cache, sessions). Incremental changes via `sql/migrations/` and playbook **06_run_db_migrations.yml** (tracked in `schema_migrations`).
- **Connection:** Config via `config.php` → `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`. In Docker, `DB_HOST=rop_website_db`, `DB_NAME=dutch_dashboard`.

## Project layout (codebase)

```
00_Codebase/php_base_001/
├── api/                    # Endpoints (one script per route)
│   ├── dutch_mt/           # Dutch.mt-specific (e.g. register_for_tournament)
│   ├── login.php, register.php, refresh.php
│   ├── create-tournament.php, health.php, health-python.php
│   └── ...
├── lib/
│   ├── api_registry.php    # SSOT: path, methods, auth, input_rules
│   ├── public_api_security.php  # Sanitize/filter for public endpoints
│   ├── jwt.php             # Create/verify JWT
│   ├── db.php              # PDO connection
│   └── python_client.php   # HTTP to Python (service key + public)
├── sql/
│   ├── init.sql            # Initial schema
│   └── migrations/         # Incremental DDL (run by playbook 06)
├── config.php              # Load .env, return config array
├── Dockerfile              # PHP 8.2 Apache + php_base_001 + dashboard frontend
└── .env                    # Not committed; copied to VPS as .env.rop_website
```

## Where PHP runs in production

- **Container:** `rop_website_php` (image `silvella/rop_website_php:latest`), port 80 → host **8081**.
- **Nginx:** Only **dashboard.reignofplay.com** proxies `location /api/` to `http://127.0.0.1:8081`. So the canonical public API base for browsers is `https://dashboard.reignofplay.com`.
- **Dutch.mt / dutch.reignofplay.com:** Static content only; no `/api/` on that host. Frontend uses client-side detection to send API requests to `https://dashboard.reignofplay.com` (see playbooks doc and API doc).
