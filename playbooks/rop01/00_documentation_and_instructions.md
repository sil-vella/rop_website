# rop01 VPS — Documentation and instructions (this project)

Reference for the **app_dev** agent when working in `playbooks/rop01/`. Setups and major configs are done by **another project**; this project only handles **PHP server–related logic** for the app on the VPS.

**Note:** The PHP backend and SQL DB on the VPS are the **general Reign of Play company website/backend/DB**, not Dutch-dashboard-only. The Dutch dashboard is one application within that stack. Services `rop_website_php` and `rop_website_db` in docker-compose reflect that; the image is built from the Dutch dashboard codebase today but is named and used as the company PHP/website stack.

---

## VPS connection (reference only)

- **Host:** `65.181.125.135`
- **SSH user (normal ops):** `rop01_user` (root is disabled after initial setup)
- **Key:** `~/.ssh/rop01_key` (ED25519)
- **Manual SSH:** `ssh -i ~/.ssh/rop01_key rop01_user@65.181.125.135`
- **Ansible inventory:** `playbooks/rop01/inventory.ini` (likely gitignored). Defines groups `rop01_root` and `rop01_user` with `ansible_host`, `ansible_user`, `ansible_ssh_private_key_file=~/.ssh/rop01_key`. Var `vm_name=rop01` in `[all:vars]`.
- **Initial setup** (SSH key, security, firewall, nginx base, etc.) is done by scripts/playbooks in the **other** project (e.g. `01_setup_ssh_key.sh`, `02_configure_security.yml`, `03_setup_firewall.yml`, `04_setup_nginx.yml`). Do not assume we own or modify those from this project.

Use this section only to understand how the VPS is reached; do not add new connectivity or structural changes from this project.

---

## 04_setup_nginx.yml and nginx template (reference)

Maintained by the other project. This section documents the current shape so we know where dutch.mt and the PHP dashboard fit.

- **Playbook:** `04_setup_nginx.yml`. Runs on `{{ vm_name }}_user`. Installs nginx, certbot; creates site configs from `templates/nginx-site.conf.j2`; obtains Let’s Encrypt SSL; sets security headers and certbot renewal cron.
- **Domain list (default `nginx_domains`):**
  - **reignofplay.com** (with www) → root `/var/www/reignofplay.com`
  - **dutch.reignofplay.com** → root `/var/www/dutch.reignofplay.com`, `backend_port: 5001`, `backend_ws_port: 8080`, `serve_flutter_web: true`
  - **dutch.mt** → same root as dutch.reignofplay.com: `/var/www/dutch.reignofplay.com`, same `backend_port` and `backend_ws_port`, `serve_flutter_web: true`
- **Docroots:** Per-domain dirs under `/var/www/<domain>`. For dutch.mt and dutch.reignofplay.com the playbook uses a single root: `/var/www/dutch.reignofplay.com` (with subdirs `downloads/`, `sponsors/images/`, `sim_players/images/`).
- **Template `nginx-site.conf.j2`:** For domains with `backend_port` and `serve_flutter_web`: serves static from `root_dir`; proxies API paths to `127.0.0.1:{{ backend_port }}` (Flask); proxies `/ws` to `backend_ws_port`; Flutter SPA at `/` with `try_files` and static caching. **No PHP** in this template — the Dutch dashboard PHP backend is not yet served by nginx.
- **Implication for this project:** When we add PHP server logic (Dutch dashboard), we will need to deploy the PHP app and configure nginx to serve it (e.g. PHP-FPM + a `location` for the dashboard, or a separate site). We do not change the overall structure of `04_setup_nginx.yml` or the list of domains; we only add what’s needed to run the PHP app (e.g. deploy files, optional nginx snippet or role for the dashboard).

---

## Nginx and web dirs on rop01 (verified on VPS)

Verified by SSH to rop01. Use this when checking or deploying web files for reignofplay.com.

**Nginx config paths (rop01):**

- **Sites enabled:** `/etc/nginx/sites-enabled/` — symlinks to `sites-available`.
- **Sites available:** `/etc/nginx/sites-available/`.
- **reignofplay.com:** `/etc/nginx/sites-available/reignofplay.com` (enabled via symlink).
- **dutch.reignofplay.com:** `/etc/nginx/sites-available/dutch.reignofplay.com` (enabled via symlink).
- **dashboard.reignofplay.com:** `/etc/nginx/sites-available/dashboard.reignofplay.com` (enabled via symlink; added by playbook `04_config_nginx.yml`). Serves from `/var/www/reignofplay.com/dashboard`.

**reignofplay.com nginx (summary):**

- **Document root:** `root /var/www/reignofplay.com;`
- **Static:** `location / { try_files $uri $uri/ =404; }`
- **Downloads:** `location /downloads/` → alias `/var/www/reignofplay.com/downloads/`
- **Security:** `location ~ /\. { deny all; }`
- **SSL:** Certbot-managed; www → 301 to non-www; HTTP → 301 to HTTPS.

**Web directory for reignofplay.com:** `/var/www/reignofplay.com`

| Path       | Type | Owner    | Notes                    |
|-----------|------|----------|--------------------------|
| `index.html` | file | www-data | Main page                |
| `downloads/`  | dir  | www-data | App binaries (APK/IPA)   |
| `sim_players/` | dir  | www-data |                          |
| `sponsors/`   | dir  | www-data |                          |

**Other docroots on VPS:** `/var/www/` also contains `dutch.reignofplay.com` and `html` (default).

---

## Dashboard ↔ PHP backend wiring (dashboard.reignofplay.com)

**PHP container (this project):**

- **Container name:** `rop_website_php` (from docker-compose: `rop_website_php` service).
- **Host port:** `8081` (mapped from container port 80). PHP app is reachable on the VPS at **`127.0.0.1:8081`**.
- **Deploy dir:** `/opt/apps/reignofplay/rop_website`. Start with `02_deploy_docker_compose.yml` and ensure containers are up (`docker compose up -d` in that dir).

**Nginx (playbook `04_config_nginx.yml`):**

- **dashboard.reignofplay.com** serves static files from `/var/www/reignofplay.com/dashboard` and proxies **`/api/`** to the PHP backend:
  - `location /api/` → `proxy_pass http://127.0.0.1:8081` (port from var `dashboard_php_backend_port`, default 8081).
- Frontend (e.g. `register.html`, `login.html`) calls `/api/register.php`, `/api/login.php`, `/api/refresh.php`, etc.; those requests go to the `rop_website_php` container.

**Summary:** Static dashboard (05_deploy_dashboard) + nginx (04_config_nginx) + PHP stack (02_deploy_docker_compose, containers running). Register/login and other `/api/*` endpoints work when all three are in place.

---

## Dutch.mt static pages ↔ API detection and wiring (cross-origin)

Static content for **dutch.reignofplay.com** and **dutch.mt** is deployed by `05b_deploy_dutch_mt.yml` to `/var/www/dutch.reignofplay.com` (e.g. `register/index.html`). Nginx for those domains **does not** proxy `/api/` to the PHP backend; only **dashboard.reignofplay.com** has `location /api/` → PHP (port 8081). So a form on dutch.reignofplay.com or dutch.mt cannot use a same-origin `/api/...` URL — the request would hit the wrong host and fail (e.g. 404 or “Network error”).

**Client-side API base detection (this project):**

- Pages under `00_Codebase/dutch_mt/` (e.g. `register/index.html`) that call the PHP API use the following logic:
  1. If **`window.DUTCH_MT_API_BASE`** is set (e.g. by a parent page or script), that value is used as the API origin (no trailing slash). Example: `https://dashboard.reignofplay.com`.
  2. Else, if the current hostname is **`dutch.reignofplay.com`**, **`dutch.mt`**, or **`*.dutch.mt`**, the API base is set to **`https://dashboard.reignofplay.com`** so that API requests are sent there.
  3. Otherwise (e.g. same-origin when served from dashboard, or localhost), the API base is left empty and requests use a relative path `/api/...`.

- The register form POSTs to `{API_BASE}/api/dutch_mt/register_for_tournament.php`. When the user is on dutch.reignofplay.com or dutch.mt, the request goes to `https://dashboard.reignofplay.com/api/dutch_mt/register_for_tournament.php` (cross-origin). The PHP endpoint sends **`Access-Control-Allow-Origin: *`**, so the browser allows the response.

**Summary:** Dutch.mt static pages detect when they are served from dutch.reignofplay.com or dutch.mt and wire API calls to dashboard.reignofplay.com; no nginx change on the dutch.mt host is required. To override (e.g. local dev or a different backend), set `window.DUTCH_MT_API_BASE` before the form script runs.

---

## DB migrations (playbook 06)

Migrations update **only** the installed database (new tables, columns, etc.) without touching init.sql or redeploying the stack.

- **Location:** `00_Codebase/php_base_001/sql/migrations/`. Files run in **sorted order** by filename (e.g. `000_bootstrap.sql`, `001_add_role_to_users.sql`).
- **Tracking:** Table `dutch_dashboard.schema_migrations` records which migrations have been applied (`migration_id`, `applied_at`). Playbook 06 checks this and runs only migrations not yet applied.
- **Bootstrap:** `000_bootstrap.sql` creates `schema_migrations` (safe to run first; idempotent).
- **Adding changes:** Add a new `.sql` file (e.g. `002_add_some_table.sql`) with `USE dutch_dashboard;` and your DDL. Run playbook 06; it will run the new file once and record it.
- **Run:** `ansible-playbook -i playbooks/rop01/inventory.ini playbooks/rop01/06_run_db_migrations.yml -e vm_name=rop01` (after stack is up).

---

## Scope of this project in playbooks/rop01

- **We do not:** Implement or change VPS connectivity, SSH keys, inventory, firewall, base nginx/Docker setup, or other major structural config. That stays in the other project.
- **We do:** PHP server–related logic for the **company (Reign of Play) website/backend** on the VPS. That includes, for example:
  - Building and deploying the company PHP/website image (Dockerfile: `00_Codebase/php_base_001/Dockerfile`, build context: `00_Codebase`; `01_build_and_push_php_docker.py`, `rop_website_php` in docker-compose).
  - The company SQL DB (`rop_website_db` in docker-compose) used by the PHP backend (e.g. database `dutch_dashboard`).
  - **Deploy playbook** `02_deploy_docker_compose.yml`: deploys **only** the RoP website stack (PHP + MariaDB). Uses a **dedicated app dir** `/opt/apps/reignofplay/rop_website` (does not touch the main app at `/opt/apps/reignofplay/dutch`). Copies `docker-compose.yml` (which contains only `rop_website_php` and `rop_website_db`), `init.sql`, **migrations** (`sql/migrations/`), and **VPS `.env.rop_website`** from local `00_Codebase/php_base_001/.env` when that file exists; otherwise creates a placeholder. Waits for ports 3307 and 8081. Run with: `ansible-playbook -i playbooks/rop01/inventory.ini playbooks/rop01/02_deploy_docker_compose.yml -e vm_name=rop01`.
  - **DB migrations playbook** `06_run_db_migrations.yml`: updates **only** the installed DB (dutch_dashboard). Copies `sql/migrations/` to the VPS, checks the `schema_migrations` table for already-applied migrations, and runs any new migration files in sorted order (e.g. `000_bootstrap.sql`, `001_add_role_to_users.sql`). Add new tables/columns by adding a new `.sql` file under `00_Codebase/php_base_001/sql/migrations/`; the playbook will run it once and record it. Run after deploy or anytime: `ansible-playbook -i playbooks/rop01/inventory.ini playbooks/rop01/06_run_db_migrations.yml -e vm_name=rop01`.
  - **Dashboard users update** `07_update_dashboard_users.py`: updates `dutch_dashboard.users` from `playbooks/rop01/templates/.update_users` (YAML list under `dashboard_users`). Uses SSH connection from `playbooks/rop01/inventory.ini` ([rop01_user] group: ansible_host, ansible_user, ansible_ssh_private_key_file). Edit `templates/.update_users` with username, email, role, password (plain; hashed via `rop_website_php` on the host). Run from repo root: `python3 playbooks/rop01/07_update_dashboard_users.py`. New users are INSERTed; existing users are UPDATEd only for columns that differ.
  - **Static web deploy** `03_deploy_reignofplay_web.yml`: syncs the contents of local `00_Codebase/html_js_css_base_001` to `/var/www/reignofplay.com` on the VPS. Does not delete existing subdirs (e.g. `downloads/`, `sim_players/`, `sponsors/`); sets ownership to www-data. Run: `ansible-playbook -i playbooks/rop01/inventory.ini playbooks/rop01/03_deploy_reignofplay_web.yml -e vm_name=rop01`.
  - **Nginx dashboard site** `04_config_nginx.yml`: adds a server block for `dashboard.reignofplay.com` serving from `/var/www/reignofplay.com/dashboard`. Ensures the dashboard docroot exists, deploys the site config, enables it, and reloads nginx. Run: `ansible-playbook -i playbooks/rop01/inventory.ini playbooks/rop01/04_config_nginx.yml -e vm_name=rop01`. For HTTPS, run certbot for `dashboard.reignofplay.com` after the first deploy.
  - **Reign of Play apex HTTPS** `04b_ensure_reignofplay_com_ssl.yml`: ensures `reignofplay.com` has an HTTPS (443) server block so `https://reignofplay.com` serves the main site (root `/var/www/reignofplay.com`), not the dashboard. Without it, nginx uses the default 443 server (dashboard) for the apex. Deploys a snippet and adds an include to the existing reignofplay.com config. Run: `ansible-playbook -i playbooks/rop01/inventory.ini playbooks/rop01/04b_ensure_reignofplay_com_ssl.yml -e vm_name=rop01`.
  - **Dashboard deploy** `05_deploy_dashboard.yml`: syncs the contents of local `00_Codebase/00_dashboard` to `/var/www/reignofplay.com/dashboard` (dashboard.reignofplay.com). Sets ownership to www-data. Run: `ansible-playbook -i playbooks/rop01/inventory.ini playbooks/rop01/05_deploy_dashboard.yml -e vm_name=rop01`.
  - **Dutch.mt deploy** `05b_deploy_dutch_mt.yml`: syncs `00_Codebase/dutch_mt` to `/var/www/dutch.reignofplay.com` (dutch.reignofplay.com / dutch.mt). Those pages use client-side API detection to call dashboard.reignofplay.com for `/api/`; see **Dutch.mt static pages ↔ API detection and wiring** above. Run: `ansible-playbook -i playbooks/rop01/inventory.ini playbooks/rop01/05b_deploy_dutch_mt.yml -e vm_name=rop01`.
  - Deploying/configuring the PHP backend on the VPS (e.g. under `/var/www/dutch.reignofplay.com` or a dedicated path; dutch.mt shares that root per `04_setup_nginx.yml`).
  - Nginx (or similar) config **only for the PHP app** — e.g. PHP-FPM, a `location` or include for the dashboard, env — without changing the overall domain/site structure defined in `04_setup_nginx.yml`.
  - Any playbook tasks or vars that are strictly about running and maintaining the company PHP/website stack on the existing rop01 VPS.

When adding or changing playbooks under `playbooks/rop01/`, limit changes to the above PHP-app scope; defer connectivity and structural changes to the other project.

---

## RoP website deployment — no main app

This project’s **docker-compose** and deploy playbook **do not include or interact with the main app** (Flask, MongoDB, Redis, Grafana, Prometheus, Dart):

- **Compose in this repo:** Contains **only** `rop_website_php` and `rop_website_db`. No main app services.
- **Dedicated app dir on VPS:** `/opt/apps/reignofplay/rop_website`. The main app (if any) lives elsewhere (e.g. `/opt/apps/reignofplay/dutch`); this playbook does not create or touch that path.
- **Separate config:** Uses **`.env.rop_website`** only; no main `.env` or Flask/Mongo/Redis secrets.
- **Separate data:** MariaDB data in `/opt/apps/reignofplay/rop_website/data/website_mysql`.
- **Ports:** PHP **8081**, MariaDB **3307**.
- **Optional:** PHP can call an external API (e.g. `PYTHON_API_BASE_URL` in `.env.rop_website`) for health or create-tournament; that is outbound only and does not require the main app to be in this compose.

---

## Before you deploy — manual checklist

Do these **before** running `02_deploy_docker_compose.yml` (or before the first PHP/DB deploy):

1. **Build and push the PHP image**
   - From repo root: `python3 playbooks/rop01/01_build_and_push_php_docker.py`
   - Requires: Docker running, `docker login` to Docker Hub.
   - Result: `silvella/rop_website_php:latest` (or your `DOCKER_USERNAME`/`ROP_WEBSITE_IMAGE_NAME`) available for pull on the VPS.

2. **Prepare `00_Codebase/php_base_001/.env` locally (recommended)**
   - The playbook **copies this file to the VPS** as `.env.rop_website` when it exists. So keep your values in `00_Codebase/php_base_001/.env` and run the playbook to deploy them.
   - The file must contain at least: `PYTHON_API_BASE_URL`, `DUTCH_MT_DASHBOARD_SERVICE_KEY`, `JWT_SECRET`, `DB_USER`, `DB_PASSWORD`. For the MariaDB container also add: `MARIADB_ROOT_PASSWORD`, `MARIADB_USER`, `MARIADB_PASSWORD` (can match `DB_USER`/`DB_PASSWORD` if using one DB user). Add `DB_HOST`/`DB_NAME` if you like; compose overrides `DB_HOST` and `DB_NAME` for the PHP container.
   - If you do **not** have this local `.env`, the playbook creates a placeholder on the VPS (only when `.env.rop_website` is missing); then edit `/opt/apps/reignofplay/rop_website/.env.rop_website` on the VPS and set the values.

3. **Inventory**
   - Ensure `playbooks/rop01/inventory.ini` exists and has `vm_name=rop01` and the `rop01_user` host (and key path) so the deploy playbook can run.

4. **Optional — nginx for the dashboard**
   - To serve the dashboard at a URL (e.g. dutch.mt/dashboard or a subdomain), nginx must be configured to proxy to port **8081** (or to the PHP container). That is separate from this playbook; do it when you’re ready to expose the dashboard.

After the above, run: `ansible-playbook -i playbooks/rop01/inventory.ini playbooks/rop01/02_deploy_docker_compose.yml -e vm_name=rop01`. The playbook deploys only to `/opt/apps/reignofplay/rop_website` and starts only `rop_website_php` and `rop_website_db`; it does not start or modify any main app containers.
