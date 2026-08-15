# Mailing system (contact forms + SMTP)

How Reign of Play sends email from the PHP backend: SMTP config, contact-form API, **CORS**, and the **`source` field** (often mistaken for Origin validation).

---

## Overview

| Piece | Location | Role |
|-------|----------|------|
| SMTP send | `00_Codebase/php_base_001/lib/mail_helper.php` | Socket SMTP (TLS/SSL); fallback `mail()` if SMTP disabled |
| Config | `config.php` ← env / `.env.rop_website` | `MAIL_*` / `MAIL_SMTP_*` |
| Contact API | `api/inquiry.php` (alias `api/contact.php`) | Public JSON endpoint; sends inbox mail after response |
| Registry rules | `lib/api_registry.php` → `inquiry` | Validates `name`, `email`, `message`, optional `source` / `platform` |
| CORS helpers | `lib/public_api_security.php` | `Access-Control-Allow-Origin: *`, OPTIONS handling |

**Runtime that can send mail:** only the **`rop_website_php` Docker container** (env via `/opt/apps/reignofplay/rop_website/.env.rop_website`).

**Host PHP-FPM** (e.g. portfolio docroot) does **not** load that env and must **not** be used for mail. Frontends post to `https://dashboard.reignofplay.com/api/contact.php`.

---

## Production SMTP (current)

Production uses **Zoho** (not Gmail). Values live in `00_Codebase/php_base_001/.env.rop_website` locally and are copied to the VPS by `02_deploy_docker_compose.yml`.

| Variable | Typical production |
|----------|-------------------|
| `MAIL_SMTP_ENABLED` | `1` |
| `MAIL_SMTP_HOST` | `smtppro.zoho.eu` |
| `MAIL_SMTP_PORT` | `465` |
| `MAIL_SMTP_ENCRYPT` | `ssl` |
| `MAIL_SMTP_USER` | mailbox used to AUTH (e.g. `admin@…`) |
| `MAIL_SMTP_PASSWORD` | mailbox / app password |
| `MAIL_FROM` | same as SMTP user (recommended) |
| `MAIL_FROM_NAME` | e.g. `ReignOfPlay` |
| `MAIL_CONTACT_TO` | optional; defaults to `MAIL_FROM` if unset |

Do **not** commit real passwords. Restart / recreate `rop_website_php` after env changes so the container picks them up.

Verify inside the container:

```bash
docker exec rop_website_php printenv | grep '^MAIL_'
```

---

## What gets sent

### Contact / inquiry (`mail_send_contact`)

- **To:** `MAIL_CONTACT_TO` or `MAIL_FROM`
- **From:** `MAIL_FROM` / `MAIL_FROM_NAME`
- **Reply-To:** submitter’s email
- **Subject:** `Contact form: {name}` plus optional ` — {source}` (e.g. ` — Portfolio`)
- **Body:** name, email, optional source/platform, message

Used by `api/inquiry.php` (and `api/contact.php` alias). Response is returned **before** SMTP runs (`fastcgi_finish_request` / flush) so the browser is not blocked by slow SMTP.

### Tournament registration

- `mail_send_registration_confirmation` / `mail_send_tournament_full` from `api/dutch_mt/register_for_tournament.php`
- Registration flow may require a successful send (see that endpoint)

---

## Contact API

| Item | Value |
|------|--------|
| URL | `POST https://dashboard.reignofplay.com/api/contact.php` |
| Canonical script | `api/inquiry.php` (alias keeps old path for clients / ad-block lists) |
| Auth | None (public) |
| Body | JSON |

### Accepted JSON fields

| Field | Required | Rules |
|-------|----------|--------|
| `name` | yes | Letters / spaces / `-` `'` `.` only (`\p{L}`…); max 255 |
| `email` | yes | Valid email; max 255 |
| `message` | yes | **min 10**, max 3000 characters |
| `source` | no | Exactly one of: **`ROP`**, **`Dutch`**, **`Portfolio`** |
| `recipient` | no | Inbox email; must be on `MAIL_CONTACT_ALLOWLIST` |
| `platform` | no | Exactly one of: **`iOS`**, **`Android`** (Dutch app form) |

Invalid `source` → `400` + `Invalid format for field: source` (mail never runs).  
Short `message` → `400` + `Field message is too short`.

### Per-form recipient (inbox)

Forms may pass an optional JSON field **`recipient`** (also via HTML `data-recipient` on the form).

| Env | Purpose |
|-----|---------|
| `MAIL_CONTACT_TO` | Default inbox when `recipient` omitted |
| `MAIL_CONTACT_ALLOWLIST` | Comma-separated emails the API will accept as `recipient` (open-relay prevention) |
| `MAIL_CONTACT_BY_SOURCE` | Optional map `ROP:a@x.com,Dutch:b@x.com,Portfolio:c@x.com` used when `recipient` is omitted |

**Rules**

1. If `recipient` is set, it **must** be on `MAIL_CONTACT_ALLOWLIST` (case-insensitive). Otherwise `400` + `Invalid or disallowed recipient`.
2. If allowlist is empty, only the default `MAIL_CONTACT_TO` / `MAIL_FROM` is allowed.
3. Arbitrary external addresses are never accepted — this is intentional.

**Frontend:** set `data-recipient="inbox@example.com"` on the contact `<form>`; contact JS includes it in the POST body when present.

Example `.env.rop_website`:

```env
MAIL_CONTACT_TO=admin@reignofplay.com
MAIL_CONTACT_ALLOWLIST=admin@reignofplay.com,dutch@reignofplay.com,hello@reignofplay.com
MAIL_CONTACT_BY_SOURCE=ROP:admin@reignofplay.com,Dutch:dutch@reignofplay.com,Portfolio:hello@reignofplay.com
```

Then set each site’s `data-recipient` (or rely on `MAIL_CONTACT_BY_SOURCE` + `source` alone).

Registry SSOT: `lib/api_registry.php` → `inquiry.input_rules.source.pattern` = `/^(ROP|Dutch|Portfolio)$/`.

### Which frontend sends which `source`

| Site | Host | `source` value | JS / form |
|------|------|----------------|-----------|
| Main site | reignofplay.com | `ROP` | `html_js_css_base_001` contact script |
| Dutch landing | dutch.mt / dutch.reignofplay.com | `Dutch` | `dutch/static_landing_js/contact.js` |
| Portfolio | portfolio.reignofplay.com | `Portfolio` | `portfolio` form `data-source` + `assets/js/contact.js` |

All of the above resolve API base to **`https://dashboard.reignofplay.com`** when not same-origin with the dashboard.

---

## CORS (cross-origin)

Contact and other public APIs are called **cross-origin** from static/PHP sites on other hostnames to `dashboard.reignofplay.com`.

### What we do

In `lib/public_api_security.php` → `public_api_send_cors_headers()`:

```http
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: POST, OPTIONS
Access-Control-Allow-Headers: Content-Type
```

- Browser **preflight:** `OPTIONS /api/contact.php` → **204** (handled in `inquiry.php` before body validation).
- Browser **POST:** allowed from any Origin; PHP does **not** check `Origin` / `Referer` for allowlisting.

Nginx for `dashboard.reignofplay.com` proxies `/api/` to `127.0.0.1:8081` and does **not** implement an Origin allowlist for contact.

### What we do **not** do

| Myth | Reality |
|------|---------|
| “Origin must be on an allowlist” | **No** Origin allowlist in PHP or dashboard nginx for this API |
| “CORS blocks portfolio / dutch” | CORS headers are `*`; preflight returns 204 |
| “`source` is the HTTP Origin” | **`source` is a form tag** (`ROP` \| `Dutch` \| `Portfolio`), validated by regex — unrelated to the `Origin` header |

If mail “fails” from the browser but curl works, check nginx access for **`400`** on `POST /api/contact.php` and the JSON `error` (invalid `source`, short `message`, etc.). Do not assume Origin rejection.

### Frontend UX note

Older contact JS showed success on a timer without reading the response (fire-and-forget). Prefer awaiting `fetch` and showing `data.error` on non-success so validation failures are visible. Portfolio `assets/js/contact.js` does this.

---

## Request flow

```text
Browser (portfolio / dutch / reignofplay.com)
  │  OPTIONS  → dashboard /api/contact.php  → 204 + CORS *
  │  POST JSON { name, email, message, source }
  ▼
nginx dashboard.reignofplay.com  →  proxy /api/ → rop_website_php:80
  ▼
inquiry.php
  → CORS headers
  → api_registry + public_api_filter_input
  → 200 JSON success (or 400 validation error)
  → (after response) mail_send_contact → Zoho SMTP
```

---

## Env loading (important)

| Process | Loads SMTP? | Notes |
|---------|-------------|--------|
| `rop_website_php` (compose `env_file: .env.rop_website`) | **Yes** | Only path that should send contact mail in prod |
| Host `php8.3-fpm` serving `/var/www/portfolio…` | **No** (unless a local `.env` is added) | Local `/api/contact.php` on portfolio would not use Zoho |
| Static sites | N/A | JS posts to dashboard API |

Portfolio must keep calling the **dashboard** contact URL, not same-origin portfolio `/api/` for mail.

---

## Ops / debugging

1. **Container has MAIL_*** — `docker exec rop_website_php printenv | grep MAIL_`
2. **Access log** — `/var/log/nginx/dashboard.reignofplay.com.access.log`  
   - `OPTIONS` 204 + `POST` 200 → validation passed; check inbox / SMTP logs  
   - `POST` 400 → validation; inspect response body or PHP error log (`inquiry.php:` / `public_api_security_fail:`)
3. **PHP logs** — `docker logs rop_website_php` (origin/referer/source logged on inquiry; failures on security fail / SMTP)
4. **SMTP failures** — `mail_send_contact failed…`, `SMTP connect failed`, `SMTP AUTH failed` in error log
5. **Rebuild** — mail/registry code is **baked into** `silvella/rop_website_php:latest`; after changing `lib/` or `api/`, run `01_build_and_push_php_docker.py` and `docker compose pull && up -d` on the VPS

### Quick curl checks

```bash
# Valid Portfolio source + Origin header (simulates browser)
curl -sS -X POST 'https://dashboard.reignofplay.com/api/contact.php' \
  -H 'Content-Type: application/json' \
  -H 'Origin: https://portfolio.reignofplay.com' \
  -d '{"name":"Test User","email":"you@example.com","message":"Long enough message","source":"Portfolio"}'

# Invalid source (expect 400)
curl -sS -X POST 'https://dashboard.reignofplay.com/api/contact.php' \
  -H 'Content-Type: application/json' \
  -d '{"name":"Test User","email":"you@example.com","message":"Long enough message","source":"Web"}'
```

---

## Alternative providers (Gmail, etc.)

Same env shape works for Gmail or other SMTP relays:

```env
MAIL_SMTP_ENABLED=1
MAIL_SMTP_HOST=smtp.gmail.com
MAIL_SMTP_PORT=587
MAIL_SMTP_ENCRYPT=tls
MAIL_SMTP_USER=you@gmail.com
MAIL_SMTP_PASSWORD=app-password-here
MAIL_FROM=you@gmail.com
MAIL_FROM_NAME=ReignOfPlay
```

Gmail needs 2FA + an **App Password**. Port **465** + `MAIL_SMTP_ENCRYPT=ssl` is also supported (Zoho production style).

Optional: PHPMailer via Composer — not required; current code uses native sockets in `mail_helper.php`.

---

## Related docs

- [ENVIRONMENT.md](ENVIRONMENT.md) — env files and `config.php`
- [API.md](API.md) — registry and public security helpers
- [DOCKER.md](DOCKER.md) / [DEPLOYS.md](DEPLOYS.md) — image and VPS deploy
- Portfolio upload: `playbooks/rop01/18_upload_portfolio_site.yml`
- Stack env deploy: `playbooks/rop01/02_deploy_docker_compose.yml`
