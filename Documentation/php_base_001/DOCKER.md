# php_base_001 — Docker

## Overview

The PHP backend is shipped as a **Docker image** that includes php_base_001 (API, lib, config) and the Dutch dashboard frontend (HTML/JS/CSS). The image runs under **docker-compose** on the VPS together with MariaDB. No `.env` is baked into the image; configuration is provided at runtime via `env_file` and `environment` in compose.

## Dockerfile

**Location:** `00_Codebase/php_base_001/Dockerfile`  
**Build context:** `00_Codebase/` (parent of `php_base_001/`), so the Dockerfile can `COPY` both `php_base_001/` and `00_dashboard/Dutch/`.

### Base and extensions

- **Base image:** `php:8.2-apache`
- **Apache:** `a2enmod rewrite`
- **PHP:** `docker-php-ext-install pdo pdo_mysql`

### Document root

- **APACHE_DOCUMENT_ROOT:** `/var/www/html`
- Apache config is adjusted so that the document root is `/var/www/html`.

### What is copied into the image

| Source (relative to build context) | Destination in image |
|------------------------------------|----------------------|
| `php_base_001/config.php` | `/var/www/html/config.php` |
| `php_base_001/api` | `/var/www/html/api` |
| `php_base_001/lib` | `/var/www/html/lib` |
| `00_dashboard/Dutch/index.html` | `/var/www/html/index.html` |
| `00_dashboard/Dutch/login.html` | `/var/www/html/login.html` |
| `00_dashboard/Dutch/css` | `/var/www/html/css` |
| `00_dashboard/Dutch/js` | `/var/www/html/js` |

- **Not copied:** `.env`, `sql/` (schema is used on the host via compose volume for DB init; PHP code in the image does not need sql at runtime for normal requests).
- **Post-build:** A `RUN sed` replaces `../dsh_php_base_001` with `''` in the dashboard HTML so API calls are same-origin inside the container. Final step: `chown -R www-data:www-data /var/www/html`.

### Expose

- **80** — Apache listens on 80 inside the container; the host maps it to **8081** in docker-compose.

---

## Build and push (local)

**Script:** `playbooks/rop01/01_build_and_push_php_docker.py`

- **Purpose:** Build the image from the current codebase and push it to Docker Hub so the VPS can pull it.
- **Project root:** Script assumes it is run from **app_dev** (repo root). It derives paths from `playbooks/rop01/` → project root = `../..`.
- **Paths used:**
  - **Build context:** `PROJECT_ROOT/00_Codebase`
  - **Dockerfile:** `00_Codebase/php_base_001/Dockerfile`
- **Image name:** `{DOCKER_USERNAME}/{ROP_WEBSITE_IMAGE_NAME}:{IMAGE_TAG}`  
  Defaults: `DOCKER_USERNAME=silvella`, `ROP_WEBSITE_IMAGE_NAME=rop_website_php`, `IMAGE_TAG=latest` (overridable via env).
- **Steps:** Prompts for confirmation (unless non-interactive), runs `docker build`, then `docker push`. If `IMAGE_TAG` is not `latest`, also tags and pushes `latest`.

**Run (from app_dev):**

```bash
python3 playbooks/rop01/01_build_and_push_php_docker.py
```

**Requirements:** Docker daemon running, `docker login` to Docker Hub.

---

## docker-compose (RoP website stack)

**Location (repo):** `docker-compose.yml` in **app_dev** (repo root).  
**Location (VPS):** Copied by playbook **02_deploy_docker_compose.yml** to `/opt/apps/reignofplay/rop_website/docker-compose.yml`.

### Services

| Service | Image | Container name | Host port | Role |
|---------|--------|----------------|-----------|------|
| **rop_website_db** | mariadb:11 | rop_website_db | 3307→3306 | MariaDB; database `dutch_dashboard`. |
| **rop_website_php** | silvella/rop_website_php:latest | rop_website_php | 8081→80 | PHP backend (Apache). |

### rop_website_db

- **env_file:** `.env.rop_website` (in the same directory as docker-compose on the VPS).
- **environment:** `MARIADB_DATABASE=dutch_dashboard`.
- **Volumes:**
  - Data: `/opt/apps/reignofplay/rop_website/data/website_mysql` → `/var/lib/mysql`.
  - Init script: `./00_Codebase/php_base_001/sql/init.sql` → `/docker-entrypoint-initdb.d/01_init.sql` (read-only). Path `./00_Codebase/...` is relative to the compose file directory on the VPS (so the playbook must copy `init.sql` there; see DEPLOYS.md).
- **Healthcheck:** `healthcheck.sh --connect --innodb_initialized` (MariaDB image default). Compose waits for healthy before starting PHP.

### rop_website_php

- **image:** `silvella/rop_website_php:latest` (pull from Docker Hub after 01_build_and_push).
- **env_file:** `.env.rop_website`.
- **environment:** `DB_HOST=rop_website_db`, `DB_NAME=dutch_dashboard` (overrides for container network).
- **depends_on:** rop_website_db with `condition: service_healthy`.
- **ports:** 8081:80 → PHP is reachable on the VPS at **127.0.0.1:8081** (nginx proxies to this).

### Network

- **app-network:** bridge; both containers attach so PHP can connect to `rop_website_db` by hostname.

---

## Container layout (runtime)

Inside **rop_website_php**:

- `/var/www/html/` — Document root: `config.php`, `api/`, `lib/`, dashboard `index.html`, `login.html`, `css/`, `js/`.
- API URLs: e.g. `/api/login.php`, `/api/dutch_mt/register_for_tournament.php` (same-origin from the container’s perspective).
- Config: read from `config.php`, which loads env (from `.env.rop_website` via Apache/PHP env). `.env` is not in the image; it is provided on the host and passed via compose.

---

## Summary

| Step | Where | What |
|------|--------|------|
| Build | Local (app_dev) | `01_build_and_push_php_docker.py` → build from `00_Codebase/`, push `silvella/rop_website_php:latest`. |
| Deploy compose | VPS (playbook 02) | Copy `docker-compose.yml`, `.env.rop_website`, `init.sql` (and dirs); `docker compose pull` and `up -d`. |
| Run | VPS | Containers in `/opt/apps/reignofplay/rop_website`; PHP on host port **8081**; nginx (dashboard.reignofplay.com) proxies `/api/` to 127.0.0.1:8081. |
