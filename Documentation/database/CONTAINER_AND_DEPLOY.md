# MariaDB container and database deploy

This document describes how the **dutch_dashboard** database is run (MariaDB container), initialized (init.sql), and updated over time (migrations), and how this ties into the PHP codebase and deploys.

---

## Container: rop_website_db

| Item | Value |
|------|--------|
| **Service name** | rop_website_db |
| **Image** | mariadb:11 |
| **Container name** | rop_website_db |
| **Host port** | 3307 → container 3306 |
| **Network** | app-network (shared with rop_website_php) |

**Purpose:** Run MariaDB 11 and host the single database **dutch_dashboard** used by the PHP backend.

---

## Configuration (environment)

The container is configured via **.env.rop_website** (same file as the PHP container) and compose `environment`:

| Source | Variable | Effect |
|--------|----------|--------|
| env_file | MARIADB_ROOT_PASSWORD | Root password for MariaDB. |
| env_file | MARIADB_USER | Application user (e.g. rop_web). |
| env_file | MARIADB_PASSWORD | Password for that user. |
| environment | MARIADB_DATABASE=dutch_dashboard | Database created on first start if it does not exist. |

PHP uses the **same** user/password via DB_USER/DB_PASSWORD (often identical to MARIADB_USER/MARIADB_PASSWORD) so that the app connects with a dedicated user, not root.

---

## Data volume

**Host path:** `/opt/apps/reignofplay/rop_website/data/website_mysql`  
**Container path:** `/var/lib/mysql`

All MariaDB data (dutch_dashboard, system tables) persists in this volume. Removing the volume removes the data; replacing the container but keeping the volume keeps the data. The deploy playbook **02_deploy_docker_compose.yml** ensures the directory exists before `docker compose up`.

---

## Initial schema: init.sql

**Source (repo):** `00_Codebase/php_base_001/sql/init.sql`  
**On VPS:** Copied by playbook **02_deploy_docker_compose.yml** to  
`/opt/apps/reignofplay/rop_website/00_Codebase/php_base_001/sql/init.sql`.

**How it runs:** The compose file mounts this file into the container as:

```yaml
./00_Codebase/php_base_001/sql/init.sql:/docker-entrypoint-initdb.d/01_init.sql:ro
```

The official MariaDB/MySQL image runs any `.sql` (and `.sh`) scripts in `/docker-entrypoint-initdb.d/` **only on first initialization** (when the data directory is empty). So:

- **First start:** Data dir is empty → entrypoint creates DB from MARIADB_DATABASE, runs 01_init.sql → database and base tables (users, audit_log, cache, sessions) exist.
- **Later starts:** Data dir already has data → init scripts are **not** run again. Changes to init.sql do not apply to an existing volume; use **migrations** for that.

**Init script contents (summary):** Creates database `dutch_dashboard` (utf8mb4), `USE dutch_dashboard`, then creates tables: audit_log, cache, users, sessions. Commented lines show example GRANT for a dedicated user.

---

## Migrations (incremental schema updates)

**Source (repo):** `00_Codebase/php_base_001/sql/migrations/*.sql`  
**On VPS:** Copied by playbook **06_run_db_migrations.yml** to  
`/opt/apps/reignofplay/rop_website/00_Codebase/php_base_001/sql/migrations/`.

**Table:** `dutch_dashboard.schema_migrations` — stores `migration_id` (e.g. `000_bootstrap`, `001_add_role_to_users`) and `applied_at`. Created by migration **000_bootstrap.sql** (not by init.sql).

**Playbook 06 behaviour:**

1. Ensure migrations dir exists on VPS.
2. Copy all `*.sql` from repo migrations dir to VPS migrations dir.
3. List `*.sql` files, sort by name, derive migration_id (filename without .sql).
4. For each migration_id: if not present in `schema_migrations`, run the `.sql` file against `dutch_dashboard` via `docker exec rop_website_db mariadb -u$MARIADB_USER -p$MARIADB_PASSWORD dutch_dashboard`, then insert the migration_id into `schema_migrations`.
5. Optionally list applied migrations at the end.

Migrations run in **sorted order by filename**, so naming with a numeric prefix (e.g. 000_, 001_, 002_) enforces order. Each migration runs only once per environment.

**Current migrations:** See [SCHEMA.md](SCHEMA.md). Adding a new one: add `002_something.sql` (and optionally 000_bootstrap if the DB was created before migrations existed), then run playbook 06.

---

## Deploy flow (relationship with PHP codebase)

| Step | What happens | DB impact |
|------|----------------|-----------|
| **02_deploy_docker_compose.yml** | Copies docker-compose.yml, .env.rop_website, **init.sql** to VPS; creates dirs (including data/website_mysql and 00_Codebase/php_base_001/sql); runs `docker compose pull` and `docker compose up -d`. | First time: volume empty → entrypoint runs init.sql → dutch_dashboard and base tables created. Later: no change to DB. |
| **06_run_db_migrations.yml** | Copies **migrations/** to VPS; for each migration not in schema_migrations, runs it inside rop_website_db and records it. | Adds or changes tables/columns; idempotent per migration. |

PHP code (api/, lib/) is **inside the PHP Docker image** (built by 01_build_and_push_php_docker.py). It does not need the SQL files at runtime; it only needs the database to exist and be reachable at DB_HOST/DB_NAME/DB_USER/DB_PASSWORD. So:

- **Schema changes** (new tables, columns): add migration (and/or update init.sql for fresh installs), run **06**.
- **PHP code changes** (new queries, new endpoints): rebuild image (**01**), redeploy stack (**02**).

---

## Summary

| Item | Location / mechanism |
|------|----------------------|
| **Database name** | dutch_dashboard (MARIADB_DATABASE + used by PHP). |
| **Container** | rop_website_db (mariadb:11), port 3307 on host. |
| **Data** | Host volume at /opt/apps/reignofplay/rop_website/data/website_mysql. |
| **Initial schema** | init.sql → docker-entrypoint-initdb.d (first start only). |
| **Ongoing schema** | sql/migrations/*.sql applied by playbook 06, tracked in schema_migrations. |
| **PHP connection** | DB_HOST=rop_website_db, DB_NAME=dutch_dashboard, same network (app-network). |
