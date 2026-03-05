# php_base_001 — Documentation index

This folder documents the **Reign of Play company PHP backend** (`00_Codebase/php_base_001`): architecture, APIs, Docker, and codebase uploads/deploys to the rop01 VPS.

## Unified backend, multiple frontends

The **single PHP backend** (php_base_001) and **single MariaDB database** (dutch_dashboard) serve **all** Reign of Play web frontends:

| Frontend | Codebase | Deploy playbook | Served at | Uses API / DB |
|----------|----------|-----------------|-----------|----------------|
| **Dashboard** | `00_Codebase/00_dashboard` | 05_deploy_dashboard | dashboard.reignofplay.com | Yes — login, refresh, create-tournament; same-origin `/api/`. Users in dutch_dashboard. |
| **Dutch.mt** | `00_Codebase/dutch_mt` | 05b_deploy_dutch_mt | dutch.reignofplay.com, dutch.mt | Yes — register form → `/api/dutch_mt/register_for_tournament.php` (cross-origin to dashboard.reignofplay.com). Public endpoint; may use same DB later. |
| **Reign of Play main site** | `00_Codebase/html_js_css_base_001` | 03_deploy_reignofplay_web | reignofplay.com | No — static only. Shares same VPS and stack; no direct API or DB calls. |

- **Docs:** Dashboard → [../dashboard/README.md](../dashboard/README.md). Main site → [../html_js_css_base_001/README.md](../html_js_css_base_001/README.md). Dutch.mt → [../dutch_mt/README.md](../dutch_mt/README.md).
- **Database:** [../database/README.md](../database/README.md).

## Contents

| Document | Description |
|----------|-------------|
| [ARCHITECTURE.md](ARCHITECTURE.md) | High-level architecture, auth (JWT, local DB), database, and Python API integration. |
| [API.md](API.md) | API endpoints, API registry (SSOT), and public endpoint security (sanitization, filtering). |
| [DOCKER.md](DOCKER.md) | Dockerfile, image build and push, docker-compose stack (PHP + MariaDB), container layout. |
| [DEPLOYS.md](DEPLOYS.md) | Playbooks and codebase uploads: what gets deployed where, order of operations, VPS paths. |
| [ENVIRONMENT.md](ENVIRONMENT.md) | Environment variables, `.env`, `config.php`, and VPS env handling. |
| [MAIL_SMTP.md](MAIL_SMTP.md) | Gmail (or other) SMTP for sending mail: App Passwords, env vars, and how it’s wired in PHP. |

**Database:** The PHP backend uses the **dutch_dashboard** MariaDB database (container `rop_website_db`). For schema, PHP↔DB relationship, and container/deploy details, see **[../database/README.md](../database/README.md)**.

## Quick reference

- **Codebase:** `00_Codebase/php_base_001/` (API, lib, config, sql).
- **Docker image:** Built from `00_Codebase/` (php_base_001 + dashboard frontend); pushed as `silvella/rop_website_php:latest`.
- **VPS app dir:** `/opt/apps/reignofplay/rop_website` (compose, .env.rop_website, sql, containers).
- **Public API base (browser):** `https://dashboard.reignofplay.com` (nginx proxies `/api/` to PHP on 8081).
- **Static sites:** reignofplay.com → `/var/www/reignofplay.com`; dutch.reignofplay.com / dutch.mt → `/var/www/dutch.reignofplay.com`; dashboard → `/var/www/reignofplay.com/dashboard`.
- **Frontend docs:** [Documentation/dashboard/](../dashboard/README.md), [Documentation/html_js_css_base_001/](../html_js_css_base_001/README.md), [Documentation/dutch_mt/](../dutch_mt/README.md).
