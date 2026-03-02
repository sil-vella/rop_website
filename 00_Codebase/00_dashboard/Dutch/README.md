# Dutch.mt Dashboard — Frontend (dsh_html_js_css_base_001)

Static frontend for the Dutch.mt Dashboard. Served alongside the company PHP backend (`00_Codebase/php_base_001`); all API calls go to `/api/` (same-origin when served together).

## Contents

- `index.html` — Dashboard (protected); create-tournament form and logout.
- `login.html` — Login form; POSTs to `api/login.php`, stores tokens, redirects to index or `?next=...`.
- `css/dashboard.css` — Base styles.
- `js/api.js` — API base URL, token storage, fetch with Bearer and 401 → refresh/redirect to login.
- `js/auth.js` — `requireAuth`, `login`, `logout`.

## API base

Set `window.DUTCH_DASHBOARD_API_BASE` before loading `api.js` to point to the PHP backend URL. Default is `''` (same-origin). For local dev with PHP served from `php_base_001` elsewhere, set to that base URL. No JWT secret or service key in frontend.
