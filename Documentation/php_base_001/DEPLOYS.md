# php_base_001 — Deploys and codebase uploads

This document describes how the PHP backend and related code reach the **rop01 VPS**: Docker image build/push, docker-compose deploy, static site syncs, and DB migrations. All playbooks are under **playbooks/rop01/** and are run from the **app_dev** repo root with Ansible inventory `playbooks/rop01/inventory.ini` and `vm_name=rop01`.

**Nginx is not managed in this repo.** Server nginx is configured in the other project.

---

## 1. Docker image (PHP + dashboard in image)

The **PHP application code** (php_base_001 API, lib, config, and dashboard frontend) is **inside the Docker image**. It is not copied to the VPS by a separate “sync” playbook; the VPS runs the image.

| Step | Tool | What happens |
|------|------|----------------|
| **Build & push** | `playbooks/rop01/01_build_and_push_php_docker.py` | Builds from `00_Codebase/` (Dockerfile: `php_base_001/Dockerfile`), tags `silvella/rop_website_php:latest`, pushes to Docker Hub. |
| **Deploy stack** | Playbook **02_deploy_docker_compose.yml** | On VPS: copies `docker-compose.yml` and supporting files, runs `docker compose pull` (gets latest image) and `docker compose up -d`. So **any code change in php_base_001 or dashboard files that are in the image requires a new image build (01) and then re-run of 02** (pull + up). |

**When you change:** php_base_001 `api/`, `lib/`, `config.php`, or dashboard HTML/JS/CSS that are COPY’d in the Dockerfile → run **01** (build+push), then **02** (deploy compose; pull + up).

---

## 2. Playbook 02 — Deploy RoP website Docker Compose

**File:** `playbooks/rop01/02_deploy_docker_compose.yml`  
**Run:** `ansible-playbook -i playbooks/rop01/inventory.ini playbooks/rop01/02_deploy_docker_compose.yml -e vm_name=rop01`

**VPS app directory:** `/opt/apps/reignofplay/rop_website`

### What 02 does

| Task | Description |
|------|-------------|
| Create dirs | `app_dir`, `data_dir/website_mysql`, `app_dir/00_Codebase/php_base_001/sql` |
| Copy .env | If local `00_Codebase/php_base_001/.env` exists → copy to VPS as `{{ app_dir }}/.env.rop_website`. Else, if VPS already has `.env.rop_website`, leave it; else create a placeholder. |
| Copy init.sql | `00_Codebase/php_base_001/sql/init.sql` → `{{ app_dir }}/00_Codebase/php_base_001/sql/init.sql` (used by compose volume for DB init). |
| Copy docker-compose.yml | Repo root `docker-compose.yml` → `{{ app_dir }}/docker-compose.yml`. |
| Validate | Run `docker compose config` on the VPS to check syntax. |
| Prompt | Pause: “Ready to start RoP website services?” (can pipe `echo ""` for non-interactive). |
| Pull & up | `docker compose pull` then `docker compose up -d`. |
| Wait | Wait for ports 3307 (MariaDB) and 8081 (PHP). |

**Note:** 02 does **not** copy `api/` or `lib/` to the VPS; those are in the image. It only ensures the **compose file**, **env**, and **init.sql** (and dir structure) are present so that `docker compose up` can run and the DB can initialize.

---

## 3. Static sites and docroots (separate from PHP container)

These playbooks sync **static files** to web docroots on the VPS. They do **not** change the PHP container, the image, or nginx configuration.

| Playbook | Source (local) | Destination (VPS) | Served at |
|----------|----------------|-------------------|-----------|
| **03_deploy_reignofplay_web.yml** | `00_Codebase/html_js_css_base_001/` | `/var/www/reignofplay.com/` | reignofplay.com |
| **05_deploy_dashboard.yml** | `00_Codebase/00_dashboard/` | `/var/www/reignofplay.com/dashboard/` | dashboard.reignofplay.com (static part) |
| **05b_deploy_dutch_mt.yml** | `00_Codebase/dutch_mt/` | `/var/www/dutch.reignofplay.com/` | dutch.reignofplay.com, dutch.mt |

- **03:** Syncs main site; does not delete existing subdirs (e.g. downloads/, sim_players/, sponsors/). Sets ownership to www-data.
- **05:** Dashboard static (HTML/JS/CSS). Served at dashboard.reignofplay.com (nginx on the server, configured elsewhere).
- **05b:** Dutch.mt static (e.g. register form). Frontend uses API base `https://dashboard.reignofplay.com` when on dutch.reignofplay.com or dutch.mt.

**Run examples:**

```bash
ansible-playbook -i playbooks/rop01/inventory.ini playbooks/rop01/03_deploy_reignofplay_web.yml -e vm_name=rop01
ansible-playbook -i playbooks/rop01/inventory.ini playbooks/rop01/05_deploy_dashboard.yml -e vm_name=rop01
ansible-playbook -i playbooks/rop01/inventory.ini playbooks/rop01/05b_deploy_dutch_mt.yml -e vm_name=rop01
```

---

## 4. API routing (server nginx — other project)

- **dashboard.reignofplay.com** serves static from `/var/www/reignofplay.com/dashboard` and proxies **`/api/`** to **http://127.0.0.1:8081** (PHP container). Public API base URL: `https://dashboard.reignofplay.com`.
- **dutch.reignofplay.com** and **dutch.mt** serve static only; pages that need the API call dashboard.reignofplay.com (client-side).

Nginx changes are made in the **other** project — not in this repo.

---

## 5. DB migrations

**Playbook:** `06_run_db_migrations.yml`  
**Run:** `ansible-playbook -i playbooks/rop01/inventory.ini playbooks/rop01/06_run_db_migrations.yml -e vm_name=rop01`

- **Local migrations:** `00_Codebase/php_base_001/sql/migrations/*.sql` (e.g. `000_bootstrap.sql`, `001_add_role_to_users.sql`).
- **VPS:** Playbook copies migrations to `{{ app_dir }}/00_Codebase/php_base_001/sql/migrations/`, then for each file (sorted by name) checks `dutch_dashboard.schema_migrations`; if not applied, runs the SQL and records it. No need to rebuild the PHP image for schema changes; just add a new `.sql` in `sql/migrations/` and run 06.

---

## 6. Order of operations (typical)

| Order | Action | When |
|-------|--------|------|
| 1 | **01_build_and_push_php_docker.py** | After changing php_base_001 (api/lib/config) or dashboard files that are in the image. |
| 2 | **02_deploy_docker_compose.yml** | Deploy/update stack (compose, .env, init.sql, pull image, start containers). |
| 3 | **03 / 05 / 05b** | After changing static content for reignofplay.com, dashboard static, or dutch_mt. |
| 4 | **06_run_db_migrations.yml** | After adding/changing `sql/migrations/*.sql`. |

---

## 7. VPS paths summary

| Path | Purpose |
|------|---------|
| `/opt/apps/reignofplay/rop_website` | RoP website app dir: docker-compose.yml, .env.rop_website, 00_Codebase/php_base_001/sql (init.sql, migrations). |
| `/opt/apps/reignofplay/rop_website/data/website_mysql` | MariaDB data volume. |
| `/var/www/reignofplay.com` | reignofplay.com docroot (03). |
| `/var/www/reignofplay.com/dashboard` | dashboard.reignofplay.com static (05). |
| `/var/www/dutch.reignofplay.com` | dutch.reignofplay.com / dutch.mt static (05b). |

PHP application code (api/, lib/) lives **inside the container** (image), not under these VPS paths.
