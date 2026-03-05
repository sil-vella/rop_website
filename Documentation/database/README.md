# Database documentation (RoP website / dutch_dashboard)

This folder documents the **MariaDB** database used by the Reign of Play PHP backend (php_base_001): schema, relationship with the PHP codebase, and how the database is run and updated in production (container, init, migrations).

## Database at a glance

| Item | Value |
|------|--------|
| **Database name** | `dutch_dashboard` |
| **Engine** | MariaDB 11 (in Docker container `rop_website_db`) |
| **Used by** | PHP backend (php_base_001): register, login, refresh, and future use of audit_log, cache, sessions |
| **Connection from PHP** | PDO via `lib/db.php`; config from `config.php` (env: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`) |
| **Host from PHP container** | `rop_website_db` (Docker service name); from host/VPS: `127.0.0.1`, port **3307** |

## Contents

| Document | Description |
|----------|-------------|
| [SCHEMA.md](SCHEMA.md) | Tables, columns, indexes: users, audit_log, cache, sessions, schema_migrations. |
| [PHP_RELATIONSHIP.md](PHP_RELATIONSHIP.md) | How the PHP codebase connects to the DB, which endpoints use it, and connection lifecycle. |
| [CONTAINER_AND_DEPLOY.md](CONTAINER_AND_DEPLOY.md) | MariaDB container (docker-compose), init.sql, volumes, migrations playbook, and deploy flow. |

## Quick reference

- **Schema source:** `00_Codebase/php_base_001/sql/init.sql` (initial), plus `sql/migrations/*.sql` (incremental).
- **PHP connection:** `$config = require 'config.php'; $pdo = db_connect($config);` — used in `api/register.php`, `api/login.php`, `api/refresh.php`.
- **Container:** `rop_website_db` (image `mariadb:11`), data volume at `/opt/apps/reignofplay/rop_website/data/website_mysql` on VPS.
- **Migrations:** Playbook `06_run_db_migrations.yml` copies migrations to VPS and runs unapplied ones via `docker exec rop_website_db mariadb ...`; state in `schema_migrations` table.
