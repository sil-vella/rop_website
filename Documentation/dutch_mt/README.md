# Dutch.mt frontend — Documentation

This folder documents the **Dutch.mt** static frontend: codebase `00_Codebase/dutch_mt`, what it does, how it is deployed, and how it uses the **unified PHP backend and database**.

## Codebase

**Path:** `00_Codebase/dutch_mt/`

Static pages for Dutch.mt (and dutch.reignofplay.com). Currently: a **register** form (tournament/interest registration). No server-side code; HTML and JavaScript that call the **single company PHP backend** (php_base_001) for the register API.

### Layout

| Path | Description |
|------|-------------|
| `register/index.html` | Register form: username, email, password, password_confirm. POSTs to `/api/dutch_mt/register_for_tournament.php`. Shows success or error message. |

### API base (cross-origin)

Dutch.mt (and dutch.reignofplay.com) static files are served from a **different host** than the PHP API. Nginx for those domains does **not** proxy `/api/`; only **dashboard.reignofplay.com** does. So the form must call the API on another origin.

- **`window.DUTCH_MT_API_BASE`** — If set before the form script runs, that URL is used as the API base (no trailing slash).
- **Auto-detection:** If not set and the hostname is `dutch.reignofplay.com`, `dutch.mt`, or `*.dutch.mt`, the script sets the API base to **`https://dashboard.reignofplay.com`**. Requests then go to `https://dashboard.reignofplay.com/api/dutch_mt/register_for_tournament.php`.
- **Same-origin:** If the page is served from dashboard or localhost, the base is left empty and the request uses `/api/dutch_mt/register_for_tournament.php` (relative).

The PHP endpoint sends `Access-Control-Allow-Origin: *`, so the browser allows the cross-origin response.

### Backend and database

- **Register endpoint** is part of the **unified PHP backend** (php_base_001): public, no JWT; input is validated and sanitized (API registry + public_api_security). Currently it echoes the received form data; later it may persist to the **same MariaDB** (dutch_dashboard) or forward to another service.
- **Same stack:** Auth (login/register/refresh), dashboard, and dutch_mt registration all use the same php_base_001 and the same dutch_dashboard DB where applicable.
- See **[../php_base_001/README.md](../php_base_001/README.md)** and **[../database/README.md](../database/README.md)** for backend and DB documentation.

---

## Deploy

**Playbook:** `playbooks/rop01/05b_deploy_dutch_mt.yml`  
**Run:** `ansible-playbook -i playbooks/rop01/inventory.ini playbooks/rop01/05b_deploy_dutch_mt.yml -e vm_name=rop01`

| Source | Destination (VPS) | Served at |
|--------|-------------------|-----------|
| `00_Codebase/dutch_mt/` | `/var/www/dutch.reignofplay.com/` | **dutch.reignofplay.com**, **dutch.mt** (same docroot) |

After deploy, e.g. `https://dutch.mt/register/` (or `/register/index.html`) serves the register form; the form’s API calls go to dashboard.reignofplay.com as above.

---

## Related docs

- **PHP backend (unified):** [Documentation/php_base_001/](../php_base_001/README.md)
- **Database:** [Documentation/database/](../database/README.md)
- **API detection and wiring (playbooks):** playbooks/rop01/00_documentation_and_instructions.md (Dutch.mt static pages ↔ API detection and wiring).
- **Unified backend and frontends:** [Documentation/php_base_001/README.md#unified-backend-multiple-frontends](../php_base_001/README.md#unified-backend-multiple-frontends)
