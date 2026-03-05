# Gmail SMTP for PHP (Dutch.mt registration emails)

The PHP app sends registration confirmation emails. The container has **no local mail transfer agent (MTA)**, so `mail()` does not deliver. Using **Gmail’s SMTP** from PHP fixes that.

---

## 1. Gmail requirements

- **Google Account** with 2-Step Verification (2FA) enabled.  
  Without 2FA you cannot create an App Password.
- **App Password** (not your normal Gmail password):
  1. Go to [Google Account → Security](https://myaccount.google.com/security).
  2. Under “How you sign in to Google”, turn on **2-Step Verification** if needed.
  3. Under “2-Step Verification”, open **App passwords**.
  4. Create a new App Password (e.g. “Reign of Play PHP”), copy the 16-character password.
- Use that App Password **only** in server/env config; never commit it.

---

## 2. Gmail SMTP settings

| Setting    | Value              |
|-----------|--------------------|
| Host      | `smtp.gmail.com`   |
| Port      | `587` (TLS) or `465` (SSL) |
| Encryption| TLS (port 587) or SSL (port 465) |
| Username  | Your full Gmail address (e.g. `you@gmail.com`) |
| Password  | The 16-character **App Password** (no spaces) |

Port **587 with TLS** (STARTTLS) is the usual choice.

---

## 3. Environment variables

Add these to `.env` (local) and to the VPS env used by the PHP container (e.g. `.env.rop_website`). Do **not** commit real credentials.

```env
# Optional: override From address/name (defaults used if unset)
MAIL_FROM=you@gmail.com
MAIL_FROM_NAME=Dutch.mt

# Enable SMTP (e.g. Gmail). If set, PHP uses SMTP instead of mail()
MAIL_SMTP_ENABLED=1
MAIL_SMTP_HOST=smtp.gmail.com
MAIL_SMTP_PORT=587
MAIL_SMTP_ENCRYPT=tls
MAIL_SMTP_USER=your.gmail@gmail.com
MAIL_SMTP_PASSWORD=xxxx xxxx xxxx xxxx
```

- `MAIL_SMTP_ENABLED`: set to `1` (or `true`) to use SMTP; leave unset or `0` to keep using `mail()` (e.g. when an MTA is available).
- `MAIL_SMTP_PASSWORD`: the **App Password**, not your normal Gmail password.
- For port 465 (SSL), use `MAIL_SMTP_PORT=465` and `MAIL_SMTP_ENCRYPT=ssl`.

---

## 4. How it’s wired in this project

- **Config:** `config.php` reads the `MAIL_*` and `MAIL_SMTP_*` variables and exposes them to the app (see [ENVIRONMENT.md](ENVIRONMENT.md)).
- **Sending:** `lib/mail_helper.php` sends mail:
  - If SMTP is enabled (config has host/user/password), it uses **SMTP over TLS/SSL** (native PHP sockets, no extra dependencies).
  - Otherwise it falls back to PHP `mail()`.
- **Usage:** The Dutch.mt registration endpoint `api/dutch_mt/register_for_tournament.php` calls `mail_send_registration_confirmation()`. If that returns true (email sent via SMTP or `mail()`), the registration is stored; otherwise the request fails with a “Could not send confirmation email” style message.

So: set the env vars on the server (and optionally locally), redeploy/restart the PHP container so it picks up the new config, and registration confirmations will go out via Gmail SMTP.

---

## 5. Security and ops

- **Secrets:** Keep `MAIL_SMTP_USER` and especially `MAIL_SMTP_PASSWORD` out of version control. Use `.env` (gitignored) and/or the VPS env file that is not committed.
- **From address:** Gmail may rewrite the From header to the account’s address; using that address in `MAIL_FROM` avoids “sent on behalf of” and looks correct.
- **Limits:** Gmail has sending limits (e.g. 500/day for personal accounts). For high volume, consider a transactional provider (SendGrid, Mailgun, etc.) and the same SMTP pattern in `mail_helper.php` with different host/port/user/pass.
- **Testing:** After setting env and restarting, submit the Dutch.mt registration form and check that the confirmation email arrives and that the registration is stored only when the send succeeds.

---

## 6. Alternative: PHPMailer (Composer)

If you prefer a library instead of the built-in SMTP code:

1. Add Composer to the project and install PHPMailer:
   ```bash
   cd 00_Codebase/php_base_001
   composer require phpmailer/phpmailer
   ```
2. In the Dockerfile, run `composer install --no-dev` and ensure `vendor/` is in the image.
3. In `lib/mail_helper.php`, replace the SMTP block with PHPMailer:
   - `use PHPMailer\PHPMailer\PHPMailer;` (and related classes).
   - Configure SMTP with the same `MAIL_SMTP_*` and `MAIL_FROM` / `MAIL_FROM_NAME` values.
   - Call `$mail->send()` and return success/failure.

The current implementation uses **no Composer dependency**: it opens a socket to `MAIL_SMTP_HOST:MAIL_SMTP_PORT`, performs STARTTLS when `MAIL_SMTP_ENCRYPT=tls`, does SMTP AUTH LOGIN with `MAIL_SMTP_USER` / `MAIL_SMTP_PASSWORD`, and sends the message. That is enough for Gmail and most SMTP relays.
