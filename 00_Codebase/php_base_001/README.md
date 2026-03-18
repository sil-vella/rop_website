# Reign of Play — PHP Backend (php_base_001)

Company website/backend PHP. Used by the Dutch dashboard and other RoP apps. Auth is local: register and login create/verify users in the dashboard DB and issue JWTs; no proxy to the main app. Python is used for business (e.g. get-tournaments) with a service key.

## PHP requirements and installation

**You need PHP installed** to run this backend (e.g. under Apache/nginx, or PHP’s built-in web server for local dev).

- **Suggested version:** PHP 7.4+ or 8.x.
- **Check:** `php -v` and `php -m` (list loaded extensions).
- **Required extensions (usually bundled):** `json`, `hash`, `pcre`. For outbound HTTP (calls to Python API), `allow_url_fopen` must be enabled in `php.ini` (default On).
- **Required for auth:** `pdo_mysql` for user storage (register/login).

**Install (macOS):** e.g. `brew install php` (or `php@8.2`). On Linux use your distro’s package (`apt install php`, `yum install php`, etc.).

**Virtual environments:** PHP does **not** use virtual environments like Python. Use system/Homebrew PHP, Composer for deps, or Docker. See original doc for details.

## Architecture

- **User → Frontend** (e.g. `00_dashboard/Dutch/dsh_html_js_css_base_001`) → **PHP** (`php_base_001`) with `Authorization: Bearer <JWT>`.
- **PHP** verifies JWT locally (decode + signature with `JWT_SECRET`). No auth call to Python.
- **PHP → Python** for business (e.g. get-tournaments) with header `X-Service-Key: DUTCH_MT_DASHBOARD_SERVICE_KEY`.
- Company MySQL/MariaDB (e.g. `dutch_dashboard` and other DBs); phpMyAdmin for admin (see below).

## Environment variables

Create a `.env` file in `php_base_001/` (do **not** commit it). Required:

| Variable | Description |
|----------|-------------|
| `PYTHON_API_BASE_URL` | Base URL of the Python API (e.g. `https://api.example.com`) |
| `DUTCH_MT_DASHBOARD_SERVICE_KEY` | Secret key for service-to-service calls to Python |
| `JWT_SECRET` | Secret used to sign and verify JWTs (this app only) |
| `DB_HOST` | Database host (default `127.0.0.1`) |
| `DB_NAME` | Database name (default `dutch_dashboard`) |
| `DB_USER` | Database user |
| `DB_PASSWORD` | Database password |

Never expose `JWT_SECRET` or `DUTCH_MT_DASHBOARD_SERVICE_KEY` to the frontend.

**VPS (docker-compose):** The deploy playbook copies **this directory’s `.env`** to the VPS as **`.env.rop_website`** (so you can keep values here and deploy them). Include `MARIADB_ROOT_PASSWORD`, `MARIADB_USER`, `MARIADB_PASSWORD` for the MariaDB container (can match `DB_USER`/`DB_PASSWORD` if using one user), plus `DB_USER`, `DB_PASSWORD`, `PYTHON_API_BASE_URL`, `DUTCH_MT_DASHBOARD_SERVICE_KEY`, `JWT_SECRET`. If this `.env` is missing, the playbook creates a placeholder on the VPS instead.

## Database setup and phpMyAdmin

- **Database**: MySQL or MariaDB. Run `sql/init.sql` once to create the `dutch_dashboard` database and tables (users, audit_log, cache, sessions, etc.).
- **phpMyAdmin**: Install separately; point at the same MySQL/MariaDB instance. Restrict by IP/VPN and use strong DB passwords.

## API endpoints

| Endpoint | Auth | Behaviour |
|----------|------|-----------|
| `api/register.php` | None | POST username, email, password; create user in DB; returns success/error. |
| `api/login.php` | None | POST username, password; verify against DB; return access_token and refresh_token (JWT). |
| `api/refresh.php` | None | POST refresh_token; verify JWT; return new access_token and refresh_token. |
| `api/get-tournaments.php` | JWT (verified locally) | Verify JWT in PHP; then GET Python `/service/dutch/get-tournaments` with `X-Service-Key`; return Python response. |
| `api/health.php` | None | 200 and simple JSON status. |
| `api/health-python.php` | JWT (verified locally) | Verify JWT in PHP; then GET Python `/service/health` with `X-Service-Key`; return combined status (dashboard + Python). |
| `api/dutch_mt/register_for_tournament.php` | None (public) | POST username, email, password, password_confirm, optional tournament_id; validated via API registry + public security; returns success + sanitized data (no passwords). |

## API registry and public endpoint security

- **Single source of truth:** `lib/api_registry.php` defines all endpoints (path, allowed methods, auth: `public` | `jwt`). For public endpoints that accept input, `input_rules` define required fields, max/min length, pattern (regex), and filter (e.g. `email`). Add or change endpoints in the registry to keep behaviour consistent.
- **Public endpoint security:** `lib/public_api_security.php` is used by public endpoints (e.g. `api/dutch_mt/register_for_tournament.php`) to:
  - Enforce a max request body size (`PUBLIC_API_MAX_BODY_LENGTH`).
  - Validate and sanitize input using the registry’s `input_rules`: trim, strip tags, remove null bytes and control characters, apply length and pattern.
  - Reject input containing harmful patterns (e.g. script tags, javascript/vbscript URIs, common SQL keywords, null bytes). On failure, respond with 400 and a generic error message.
- New public endpoints should load the registry, check method and auth, call `public_api_enforce_body_length()`, then `public_api_filter_input($rawInput, $definition['input_rules'])` before business logic.

## Contract with Python (reference)

- `GET /service/health` — requires `X-Service-Key`. Returns 200 and `{ "success": true, "service": "python-api", "status": "ok" }`.
- `GET /service/dutch/get-tournaments` — requires `X-Service-Key`; returns tournament list. PHP verifies JWTs itself; does not call `/service/auth/validate`.

## Project layout

```
00_Codebase/php_base_001/
├── api/
│   ├── dutch_mt/
│   │   └── register_for_tournament.php   (public; registry + security)
│   ├── login.php      (local auth; issues JWT)
│   ├── refresh.php    (local refresh; no proxy)
│   ├── register.php   (create dashboard user)
│   ├── get-tournaments.php
│   ├── health.php
│   └── health-python.php
├── lib/
│   ├── api_registry.php       (SSOT: path, methods, auth, input_rules)
│   ├── public_api_security.php (sanitize + filter harmful input for public APIs)
│   ├── jwt.php        (create + verify JWT)
│   ├── db.php         (PDO connection for users)
│   └── python_client.php
├── sql/
│   └── init.sql
├── config.php
├── Dockerfile    (build context: 00_Codebase; includes dashboard frontend from 00_dashboard/Dutch/dsh_html_js_css_base_001)
├── .env          (not committed)
└── README.md
```

Dashboard frontend lives under `00_Codebase/00_dashboard/Dutch/dsh_html_js_css_base_001/`. Set `window.DUTCH_DASHBOARD_API_BASE` to this backend’s URL (or `''` for same-origin when served together).
