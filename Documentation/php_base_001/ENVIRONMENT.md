# php_base_001 — Environment and configuration

## Overview

The PHP backend reads configuration from **environment variables**. Locally, these are loaded from a **`.env`** file in `00_Codebase/php_base_001/` (not committed). On the VPS, the same variables are provided via **`.env.rop_website`** in the RoP website app dir; the deploy playbook copies from the local `.env` when present, or creates a placeholder.

No secrets are baked into the Docker image; they are supplied at runtime.

---

## config.php

**File:** `00_Codebase/php_base_001/config.php`

- **.env loading:** If `php_base_001/.env` exists and is readable, the file is parsed line by line: lines matching `KEY=value` (with optional quotes stripped) are set in `$_ENV` and `putenv()` when not already set. Comments (lines starting with `#`) are skipped.
- **Return value:** An array used by endpoints and libs:

| Key | Source | Description |
|-----|--------|-------------|
| `python_api_base_url` | `PYTHON_API_BASE_URL` | Base URL of the Python API (no trailing slash). |
| `service_key` | `DUTCH_MT_DASHBOARD_SERVICE_KEY` | Secret for service-to-service calls (X-Service-Key). |
| `jwt_secret` | `JWT_SECRET` | Secret used to sign and verify JWTs. |
| `db` | `DB_*` | Associative array: `host`, `name`, `user`, `password`. |

**Defaults (if env not set):**

- `db.host`: `127.0.0.1`
- `db.name`: `dutch_dashboard`
- `db.user`: `dutch_dash`
- Others: empty string (or as above).

---

## Required environment variables

| Variable | Used by | Description |
|----------|---------|-------------|
| `JWT_SECRET` | PHP (lib/jwt.php) | Signing key for access and refresh tokens. Must be kept secret. |
| `DB_HOST` | PHP (lib/db.php via config) | Database host. In Docker: set to `rop_website_db` by compose. |
| `DB_NAME` | PHP | Database name (e.g. `dutch_dashboard`). |
| `DB_USER` | PHP | Database user. |
| `DB_PASSWORD` | PHP | Database password. |
| `PYTHON_API_BASE_URL` | PHP (python_client) | Base URL for Python API (create-tournament, health-python, etc.). |
| `DUTCH_MT_DASHBOARD_SERVICE_KEY` | PHP (python_client) | Service key for Python endpoints that require it. |

**MariaDB container** (rop_website_db) uses the same `.env.rop_website`; it expects:

| Variable | Description |
|----------|-------------|
| `MARIADB_ROOT_PASSWORD` | Root password for MariaDB. |
| `MARIADB_USER` | Application user (e.g. `rop_web`). |
| `MARIADB_PASSWORD` | Password for that user. |
| `MARIADB_DATABASE` | Set to `dutch_dashboard` in compose. |

Often `DB_USER`/`DB_PASSWORD` match `MARIADB_USER`/`MARIADB_PASSWORD` so PHP connects as the same user.

---

## Local .env (php_base_001)

**Path:** `00_Codebase/php_base_001/.env`  
**Commit:** Do **not** commit this file.

Example (values are placeholders):

```env
# PHP / RoP website
JWT_SECRET=your_jwt_secret_here
DB_HOST=127.0.0.1
DB_NAME=dutch_dashboard
DB_USER=dutch_dash
DB_PASSWORD=your_db_password

# Python API (optional for some endpoints)
PYTHON_API_BASE_URL=https://your-python-api.example.com
DUTCH_MT_DASHBOARD_SERVICE_KEY=your_service_key

# MariaDB container (for docker-compose; can match DB_*)
MARIADB_ROOT_PASSWORD=root_password
MARIADB_USER=rop_web
MARIADB_PASSWORD=your_db_password
```

When you run **02_deploy_docker_compose.yml**, if this file exists it is copied to the VPS as **`/opt/apps/reignofplay/rop_website/.env.rop_website`**. If it does not exist, the playbook only creates a placeholder when the VPS file is also missing; then you must edit `.env.rop_website` on the VPS (or add `.env` locally and re-run 02).

---

## VPS: .env.rop_website

**Path on VPS:** `/opt/apps/reignofplay/rop_website/.env.rop_website`  
**Permissions:** 0640, owner/group the VPS user (e.g. rop01_user).

- **docker-compose** passes this file to both containers via `env_file: - .env.rop_website`.
- **PHP** sees the variables via the environment (config.php uses `getenv()` after the container has loaded the file).
- **Secrets:** Never expose `JWT_SECRET` or `DUTCH_MT_DASHBOARD_SERVICE_KEY` to the frontend or in logs.

---

## Overrides in docker-compose

The compose file sets for **rop_website_php**:

- `DB_HOST=rop_website_db` (container network hostname).
- `DB_NAME=dutch_dashboard`.

So even if `.env.rop_website` has different `DB_HOST`/`DB_NAME`, the container uses these values for PHP.

---

## Summary

| Context | Config source | Notes |
|---------|----------------|-------|
| Local dev (PHP CLI or built-in server) | `00_Codebase/php_base_001/.env` | Loaded by config.php. |
| Docker (rop_website_php) | `.env.rop_website` via env_file + compose environment | config.php uses getenv(). |
| VPS app dir | `/opt/apps/reignofplay/rop_website/.env.rop_website` | Populated by playbook 02 from local .env or placeholder. |

Adding a new env var: add it to `.env` (and document here and in README), and if the MariaDB container needs it, add it to the placeholder in **02_deploy_docker_compose.yml** so new installs get the key (value filled on VPS or via local .env).
