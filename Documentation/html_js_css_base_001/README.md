# Reign of Play main site (html_js_css_base_001) — Documentation

This folder documents the **Reign of Play company landing site**: codebase `00_Codebase/html_js_css_base_001`, what it is, how it is deployed, and its place in the **unified backend and frontends** setup.

## Codebase

**Path:** `00_Codebase/html_js_css_base_001/`

Static marketing/landing site for Reign of Play. One-page style (Hyperspace-derived), with sections for the brand, games (e.g. Dutch card game), and contact. **It does not call the PHP API**; it is HTML, CSS, and JavaScript only. Links out to other RoP properties (e.g. dutch.reignofplay.com, dashboard when relevant).

### Layout (summary)

| Path | Description |
|------|-------------|
| `index.html` | Main landing: Reign of Play brand, games (Dutch card game, Li Ma Taghmilhiex), get in touch. |
| `dutch-card-game.html` | Dutch card game info page. |
| `li-ma-taghmilhiex.html` | Li Ma Taghmilhiex game page. |
| `assets/` | CSS (main.css, noscript, etc.), JS (main.js, gallery, etc.), SASS sources. |
| `images/` | Logos and images (e.g. rop_logo, dutch game logo). |

Existing subdirs under the VPS docroot (e.g. `downloads/`, `sim_players/`, `sponsors/`) are **not** in this codebase; the deploy playbook does **not** delete them, so they are preserved when syncing.

### Backend and database

- This site is **static only**. It does not send requests to `/api/` or use the PHP backend or database directly.
- It shares the **same VPS and nginx** as the rest of the RoP stack. The **same PHP backend** (php_base_001) and **same MariaDB** (dutch_dashboard) serve the dashboard and dutch_mt; the main site is another frontend in the same ecosystem, just without API calls.
- See **[../php_base_001/README.md](../php_base_001/README.md)** and **[../database/README.md](../database/README.md)** for the unified backend and DB.

---

## Deploy

**Playbook:** `playbooks/rop01/03_deploy_reignofplay_web.yml`  
**Run:** `ansible-playbook -i playbooks/rop01/inventory.ini playbooks/rop01/03_deploy_reignofplay_web.yml -e vm_name=rop01`

| Source | Destination (VPS) | Served at |
|--------|-------------------|-----------|
| `00_Codebase/html_js_css_base_001/` | `/var/www/reignofplay.com/` | **reignofplay.com** (apex and www) |

Sync is non-destructive: existing subdirs (e.g. `downloads/`, `sim_players/`, `sponsors/`) are left in place; only contents of html_js_css_base_001 are updated.

---

## Related docs

- **PHP backend (unified):** [Documentation/php_base_001/](../php_base_001/README.md)
- **Database:** [Documentation/database/](../database/README.md)
- **Unified backend and frontends:** [Documentation/php_base_001/README.md#unified-backend-multiple-frontends](../php_base_001/README.md#unified-backend-multiple-frontends)
