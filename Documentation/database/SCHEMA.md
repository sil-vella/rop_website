# Database schema — dutch_dashboard

Database name: **dutch_dashboard**. Character set: **utf8mb4**, collation **utf8mb4_unicode_ci**. All tables use **InnoDB**.

---

## Source of truth

- **Initial schema:** `00_Codebase/php_base_001/sql/init.sql` — creates the database and base tables. Run once (e.g. by MariaDB container’s docker-entrypoint-initdb.d on first start).
- **Incremental changes:** `00_Codebase/php_base_001/sql/migrations/*.sql` — applied in sorted filename order by playbook **06_run_db_migrations.yml**; applied migrations recorded in `schema_migrations`.

---

## Tables

### users

Dashboard app users (local auth). Used by register, login, and refresh endpoints.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT UNSIGNED | AUTO_INCREMENT, PRIMARY KEY | User id. |
| username | VARCHAR(255) | NOT NULL, UNIQUE (uk_username) | Login name. |
| email | VARCHAR(255) | NOT NULL, UNIQUE (uk_email) | Email address. |
| password_hash | VARCHAR(255) | NOT NULL | Hashed password (e.g. password_hash(..., PASSWORD_DEFAULT)). |
| role | VARCHAR(64) | NOT NULL, DEFAULT 'user' | Role (e.g. user, admin). Index: idx_role. |
| created_at | DATETIME(6) | NOT NULL, DEFAULT CURRENT_TIMESTAMP(6) | Creation time. |

**Indexes:** uk_username, uk_email, idx_username, idx_role.

---

### audit_log

Optional audit trail for dashboard actions (e.g. tournament creation). Not yet written by current PHP endpoints; structure ready for future use.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT UNSIGNED | AUTO_INCREMENT, PRIMARY KEY | Log id. |
| created_at | DATETIME(6) | NOT NULL, DEFAULT CURRENT_TIMESTAMP(6) | When the action occurred. |
| user_id | VARCHAR(255) | NULL | User identifier. |
| action | VARCHAR(64) | NOT NULL | Action name. |
| payload | JSON | NULL | Optional JSON payload. |
| ip | VARCHAR(45) | NULL | Client IP. |

**Indexes:** idx_created, idx_user, idx_action.

---

### cache

Optional server-side cache (e.g. tournament list). Not yet used by current PHP; structure ready for future use.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| cache_key | VARCHAR(255) | NOT NULL, PRIMARY KEY | Cache key. |
| value | LONGTEXT | NULL | Cached value. |
| expires_at | DATETIME(6) | NOT NULL | Expiry time. |

**Indexes:** idx_expires.

---

### sessions

Optional server-side session store. Not yet used by current PHP (JWT is stateless); structure ready if server-side sessions are added later.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | VARCHAR(128) | NOT NULL, PRIMARY KEY | Session id. |
| user_id | VARCHAR(255) | NULL | User identifier. |
| payload | JSON | NULL | Session data. |
| expires_at | DATETIME(6) | NOT NULL | Expiry time. |

**Indexes:** idx_user, idx_expires.

---

### schema_migrations

Tracks which migration files have been applied. Used by playbook **06_run_db_migrations.yml** to avoid re-running migrations.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| migration_id | VARCHAR(255) | NOT NULL, PRIMARY KEY | Migration identifier (filename without .sql, e.g. 000_bootstrap, 001_add_role_to_users). |
| applied_at | DATETIME(6) | NOT NULL, DEFAULT CURRENT_TIMESTAMP(6) | When the migration was applied. |

**Created by:** `sql/migrations/000_bootstrap.sql` (not by init.sql). New installs: init.sql creates base schema; playbook 06 runs migrations including 000_bootstrap and 001_add_role_to_users. Existing installs that started from init.sql (which already has `role` on users) still run 001 safely (ALTER adds column only if missing, or can be no-op depending on version).

---

### dutch_mt_events

Events (tournaments / signup targets) for Dutch.mt. Form sends `tournament_id` = event **slug**; PHP looks up by slug and attaches the registration to this event.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT UNSIGNED | AUTO_INCREMENT, PRIMARY KEY | Event id. |
| slug | VARCHAR(64) | NOT NULL, UNIQUE (uk_slug) | Identifier from form (e.g. "spring-2025"). |
| name | VARCHAR(255) | NOT NULL | Display name. |
| description | TEXT | NULL | Optional description. |
| opens_at | DATETIME(6) | NULL | When registration opens. |
| closes_at | DATETIME(6) | NULL | When registration closes. |
| created_at | DATETIME(6) | NOT NULL, DEFAULT CURRENT_TIMESTAMP(6) | Created. |
| updated_at | DATETIME(6) | NOT NULL, ON UPDATE | Last updated. |

**Indexes:** uk_slug, idx_opens, idx_closes. **Created by:** migration 002_dutch_mt_events_and_registrations.sql.

---

### dutch_mt_registrations

One row per person per event. User data (username, email, optional password_hash) stored here so public signups don’t require a dashboard user. Same email can register for multiple events (one row per event).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT UNSIGNED | AUTO_INCREMENT, PRIMARY KEY | Registration id. |
| event_id | INT UNSIGNED | NOT NULL, FK → dutch_mt_events(id) ON DELETE CASCADE | Event. |
| username | VARCHAR(255) | NOT NULL | From form. |
| email | VARCHAR(255) | NOT NULL | From form. |
| password_hash | VARCHAR(255) | NULL | Optional; from form password. |
| registered_at | DATETIME(6) | NOT NULL, DEFAULT CURRENT_TIMESTAMP(6) | When they registered. |

**Unique:** uk_event_email (event_id, email) — one registration per email per event. **Indexes:** idx_event, idx_email. **Created by:** migration 002_dutch_mt_events_and_registrations.sql.

---

## Migrations (current)

| File | Purpose |
|------|---------|
| 000_bootstrap.sql | Creates `schema_migrations` table. |
| 001_add_role_to_users.sql | Adds `role` column and idx_role to `users` (for DBs created before role was in init.sql). |
| 002_dutch_mt_events_and_registrations.sql | Creates `dutch_mt_events` and `dutch_mt_registrations`. |

**Creating an event:** Insert into `dutch_mt_events` (slug, name, ...). The form sends `tournament_id` = slug; the register endpoint resolves slug → event_id and inserts into `dutch_mt_registrations`. Example: `INSERT INTO dutch_mt_events (slug, name) VALUES ('spring-2025', 'Spring 2025 Tournament');`

Adding a new migration: add a new `.sql` file under `00_Codebase/php_base_001/sql/migrations/` with `USE dutch_dashboard;` and your DDL. Run playbook 06; it will run the file once and insert its id into `schema_migrations`.
