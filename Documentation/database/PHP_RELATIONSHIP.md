# PHP codebase ↔ MariaDB relationship

This document describes how the **php_base_001** codebase connects to and uses the **dutch_dashboard** MariaDB database.

---

## Connection configuration

PHP gets DB settings from **config.php**, which reads environment variables (or a local `.env` file):

| Config key | Environment variable | Default | Description |
|------------|----------------------|---------|-------------|
| db.host | DB_HOST | 127.0.0.1 | Database host. In Docker: **rop_website_db** (service name). |
| db.name | DB_NAME | dutch_dashboard | Database name. |
| db.user | DB_USER | dutch_dash | Database user. |
| db.password | DB_PASSWORD | (empty) | Database password. |

**In docker-compose**, the PHP service (`rop_website_php`) has:

- `env_file: .env.rop_website` (provides DB_USER, DB_PASSWORD, etc.)
- `environment: DB_HOST=rop_website_db`, `DB_NAME=dutch_dashboard` (overrides so PHP talks to the MariaDB container by service name).

So from inside the PHP container, the DB is reached at host **rop_website_db** on port 3306 (default MySQL port); from the host/VPS, the same DB is exposed on **127.0.0.1:3307**.

---

## Connection helper: lib/db.php

**Function:** `db_connect(array $config): ?PDO`

- **Input:** The array returned by `config.php` (must have a `db` key with `host`, `name`, `user`, `password`).
- **Behaviour:** Builds DSN `mysql:host=...;dbname=...;charset=utf8mb4`, creates a PDO instance with `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION` and `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC`. On success returns the PDO instance; on missing config or connection failure returns **null** (no exception).
- **Usage:** Endpoints that need the DB do `$config = require '.../config.php'; $pdo = db_connect($config);` then check `if (!$pdo)` and return 500 or equivalent before running queries.

No connection pooling or singleton is used; each request that needs the DB calls `db_connect()` once. PDO is closed when the script ends.

---

## Which endpoints use the database

| Endpoint | Use |
|----------|-----|
| **api/register.php** | `db_connect()` → `INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)`. Creates a new user; duplicates (username/email) produce PDO exception (23000) and are handled with 409. |
| **api/login.php** | `db_connect()` → `SELECT id, username, email, password_hash, role FROM users WHERE username = ? LIMIT 1`; then `password_verify()` on the stored hash. On success, issues JWT with user id, username, email, role. |
| **api/refresh.php** | Validates refresh JWT (user_id in payload), then `db_connect()` → `SELECT id, username, email, role FROM users WHERE id = ? LIMIT 1` to re-fetch user; then issues new access and refresh tokens. |

**Not using the DB (in current code):** api/health.php, api/health-python.php, api/create-tournament.php, api/dutch_mt/register_for_tournament.php. The tables **audit_log**, **cache**, and **sessions** are defined in init.sql but are not read or written by current endpoints; they are reserved for future use.

---

## Error handling

- **DB unavailable:** `db_connect()` returns null; endpoints respond with 500 and a generic message (e.g. "Database not available", "Service unavailable").
- **Constraint violations:** e.g. duplicate username/email on register → PDOException with code 23000 → 409 and message like "Username or email already exists".
- **Other PDO errors:** Typically caught and mapped to 500 and a safe message so that DB details are not exposed to the client.

---

## Summary

| Aspect | Detail |
|--------|--------|
| **DB name** | dutch_dashboard |
| **Config** | config.php → db host/name/user/password from env (or .env). |
| **Connection** | lib/db.php → `db_connect($config)` returns PDO or null. |
| **Used by** | register.php (INSERT users), login.php (SELECT users), refresh.php (SELECT users). |
| **Container** | PHP connects to host `rop_website_db` (MariaDB container on shared Docker network). |
