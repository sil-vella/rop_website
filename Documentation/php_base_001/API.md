# php_base_001 — API reference and security

## Endpoints overview

| Endpoint | Methods | Auth | Description |
|----------|---------|------|-------------|
| `api/register.php` | POST | None | Create dashboard user (username, email, password). |
| `api/login.php` | POST | None | Verify credentials, return access_token + refresh_token (JWT). |
| `api/refresh.php` | POST | None | Exchange refresh_token for new access + refresh tokens. |
| `api/health.php` | GET | None | Simple JSON health (no DB/Python). |
| `api/health-python.php` | GET | JWT | Verify JWT, then GET Python `/service/health` with service key; return combined status. |
| `api/create-tournament.php` | POST | JWT | Verify JWT, POST body to Python `/service/dutch/create-tournaments` with service key. |
| `api/dutch_mt/register_for_tournament.php` | POST | None (public) | Tournament registration form: username, email, password, password_confirm, optional tournament_id. Validated via API registry + public security; returns sanitized data (no passwords). |

All responses are JSON. Public endpoints send `Access-Control-Allow-Origin: *` for cross-origin use (e.g. forms on dutch.reignofplay.com calling dashboard.reignofplay.com).

---

## API registry (SSOT)

**File:** `lib/api_registry.php`

Single source of truth for endpoint definitions. Each entry keyed by **logical path** (relative to `api/`, e.g. `dutch_mt/register_for_tournament`).

### Definition shape

- **methods:** `string[]` — e.g. `['POST']`, `['GET']`.
- **auth:** `'public'` | `'jwt'` — whether the endpoint requires a valid JWT.
- **input_rules:** (optional) For public endpoints that accept JSON body: per-field rules for validation and sanitization (see below).

### Functions

- **`api_registry_get_all(): array`** — Returns the full path → definition map.
- **`api_registry_get(string $path): ?array`** — Returns the definition for one path, or `null`.
- **`api_registry_method_allowed(array $definition): bool`** — True if current `REQUEST_METHOD` is in the definition’s `methods`.

### Adding or changing endpoints

1. Add or edit the path entry in `api_registry_get_all()` (methods, auth, and if public with body input, `input_rules`).
2. Implement the script under `api/` (and for public endpoints with input, use `public_api_filter_input()` with `$definition['input_rules']` as in `api/dutch_mt/register_for_tournament.php`).

---

## Public endpoint security

**File:** `lib/public_api_security.php`

Used by **public** endpoints that accept JSON body (e.g. `api/dutch_mt/register_for_tournament.php`) to enforce size limits, sanitize input, and reject harmful content.

### Constants

- **`PUBLIC_API_MAX_BODY_LENGTH`** — Max request body size in bytes (default 4096). Enforced before parsing JSON.
- **`PUBLIC_API_HARMFUL_PATTERNS`** — List of regexes; if any match the (sanitized) string value, the request is rejected (400). Includes script/iframe patterns, javascript:/vbscript:, common SQL-like keywords, null byte.

### Functions

- **`public_api_enforce_body_length(): void`** — Reads `CONTENT_LENGTH`; if &gt; `PUBLIC_API_MAX_BODY_LENGTH`, sends 413 JSON and exits. Call before `json_decode`.
- **`public_api_sanitize_string(string $value, int $maxLength): string`** — Trim, strip_tags, remove null bytes and control characters, truncate to `maxLength`.
- **`public_api_reject_harmful(string $value, string $fieldName): void`** — If any harmful pattern matches, calls `public_api_security_fail()` and exits.
- **`public_api_filter_input(array $input, array $inputRules): array`** — Validates and sanitizes `$input` using `$inputRules` (from registry). For each rule: required check, type, sanitize, length (min/max), pattern (regex), filter (e.g. `email`), then harmful check. Returns only keys defined in rules; on failure sends 400 via `public_api_security_fail()`.
- **`public_api_security_fail(string $error): void`** — Sends `Content-Type: application/json`, 400, `{ "success": false, "error": "<error>" }` and exits.

### Input rules (for registry `input_rules`)

Per-field options:

| Key | Meaning |
|-----|--------|
| `required` | `true` → field must be present and non-empty. |
| `max_length` | Max character length (sanitize truncates). |
| `min_length` | Min character length (e.g. password). |
| `pattern` | Regex; value must match (after sanitize). |
| `filter` | `'email'` → `filter_var(..., FILTER_VALIDATE_EMAIL)`. |

Example (from registry for `dutch_mt/register_for_tournament`):

- **username:** required, max_length 64, pattern `^[a-zA-Z0-9_.\-]+$`.
- **email:** required, max_length 255, filter email.
- **password:** required, min_length 8, max_length 256.
- **password_confirm:** required.
- **tournament_id:** optional, max_length 64, pattern `^[a-zA-Z0-9_.\-]+$`.

### Implementing a new public endpoint with body

1. Add the endpoint and `input_rules` in `lib/api_registry.php`.
2. In the endpoint script:
   - Require `api_registry.php` and `public_api_security.php`.
   - Get definition with `api_registry_get('path/to/endpoint')`, check `api_registry_method_allowed($definition)`.
   - Call `public_api_enforce_body_length()`.
   - Decode JSON, then `$filtered = public_api_filter_input($rawInput, $definition['input_rules'])`.
   - Run business logic on `$filtered` only; never echo raw input or passwords.

---

## Contract with Python (reference)

- **GET /service/health** — Requires `X-Service-Key`. Returns 200 and `{ "success": true, "service": "python-api", "status": "ok" }`.
- **POST /service/dutch/create-tournaments** — Requires `X-Service-Key`; body = tournament payload. PHP does not call `/service/auth/validate`; it verifies JWTs locally.

Public PHP endpoints that forward to Python without a service key (if any) use `python_post_public()` to a public Python path (e.g. `/dutch/register-for-tournament`); that is not used in the current register_for_tournament flow (which only echoes form data for now).
