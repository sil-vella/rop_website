# Dashboard frontend — Documentation

This folder documents the **Reign of Play dashboard** frontend: codebase `00_Codebase/00_dashboard`, what it does, how it is deployed, and how it uses the **unified PHP backend and database**.

## Codebase

**Path:** `00_Codebase/00_dashboard/`

Static frontend for the Dutch.mt Dashboard (login, protected dashboard, create-tournament). It does **not** run any server-side code; it is HTML, CSS, and JavaScript that call the **single company PHP backend** (php_base_001) for auth and API.

### Layout

| Path | Description |
|------|-------------|
| `Dutch/index.html` | Dashboard home (protected); create-tournament form, logout. |
| `Dutch/login.html` | Login form; POSTs to `/api/login.php`, stores tokens, redirects. |
| `Dutch/css/dashboard.css` | Styles. |
| `Dutch/js/api.js` | API base URL, token storage, `apiFetch()` with Bearer and 401 → refresh or redirect to login. |
| `Dutch/js/auth.js` | `requireAuth`, `login`, `logout`. |
| `index.html` | Top-level entry (e.g. redirect or landing). |

### API base

- **`window.DUTCH_DASHBOARD_API_BASE`** — If set before `api.js` loads, all API requests use this base URL (no trailing slash). Default is `''` (same-origin).
- In production, the dashboard is served at **dashboard.reignofplay.com**; nginx serves these static files and proxies `/api/` to the PHP container. So same-origin means `/api/*` goes to the same host (dashboard.reignofplay.com) and is proxied to PHP. No need to set `DUTCH_DASHBOARD_API_BASE` in that case.

### Backend and database

- **All API calls** go to the **unified PHP backend** (php_base_001): `/api/login.php`, `/api/refresh.php`, `/api/create-tournament.php`, etc.
- **Auth and user data** come from the **same MariaDB database** (`dutch_dashboard`) used by the rest of the company stack. Register (if used) and login create/verify users in that DB; JWTs are issued and verified by PHP.
- See **[../php_base_001/README.md](../php_base_001/README.md)** and **[../database/README.md](../database/README.md)** for backend and DB documentation.

---

## Deploy

**Playbook:** `playbooks/rop01/05_deploy_dashboard.yml`  
**Run:** `ansible-playbook -i playbooks/rop01/inventory.ini playbooks/rop01/05_deploy_dashboard.yml -e vm_name=rop01`

| Source | Destination (VPS) | Served at |
|--------|--------------------|-----------|
| `00_Codebase/00_dashboard/` | `/var/www/reignofplay.com/dashboard/` | **dashboard.reignofplay.com** |

Nginx for dashboard.reignofplay.com is configured by playbook **04_config_nginx.yml**: static from that docroot, `location /api/` proxied to the PHP backend (127.0.0.1:8081). So after deploy, the dashboard pages and the API they call are both available under the same host.

---

## Related docs

- **PHP backend:** [Documentation/php_base_001/](../php_base_001/README.md)
- **Database:** [Documentation/database/](../database/README.md)
- **Unified backend and frontends:** [Documentation/php_base_001/README.md#unified-backend-multiple-frontends](../php_base_001/README.md#unified-backend-multiple-frontends)
