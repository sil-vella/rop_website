# How to set up mail to Gmail (VPS mailbox + Gmail inbox)

This guide describes how to:

1. **Receive** mail on your VPS mailbox (e.g. `admin@reignofplay.com`).
2. **Store** it in a Gmail inbox (forward or fetch).
3. **Delete mail from the VPS** after Gmail has successfully received it (so the VPS doesn’t fill up).
4. **Reply from the client (Gmail)** using your VPS domain address so replies appear as `admin@reignofplay.com`.

Prerequisites: the VPS mail stack is set up (playbook `13_setup_mail.yml`) and MX for your domain points to the VPS.

---

## Overview

- **VPS:** Postfix + Dovecot; mailboxes under `/var/mail/vhosts/<domain>/<user>/`.
- **Goal:** Use Gmail as the main client (read/store mail) but send and reply as `user@yourdomain.com` via the VPS.

Two main patterns:

| Pattern | Incoming (VPS → Gmail) | Outgoing (Gmail → world as VPS) |
|--------|-------------------------|----------------------------------|
| **A. Gmail fetches + Send as** | Gmail fetches from VPS (IMAP/POP3) | Gmail “Send mail as” using VPS SMTP |
| **B. VPS forwards to Gmail** | Postfix forwards each message to Gmail | Same: Gmail “Send mail as” using VPS SMTP |

Pattern A keeps a copy on the VPS and in Gmail. Pattern B only stores in Gmail (VPS just forwards). Both allow “reply using our VPS domain” from Gmail.

---

## Option A: Gmail fetches from VPS, replies as VPS (recommended)

### 1. Gmail: “Check mail from other accounts”

- Gmail → **Settings** (gear) → **See all settings** → **Accounts and Import**.
- **Check mail from other accounts** → **Add a mail account**.
- Enter the VPS address (e.g. `admin@reignofplay.com`).
- Choose **Import emails from my other account (POP3)** or, if your server is reachable and you prefer, **Link accounts with Gmailify** (IMAP) if offered.
- **POP3** (typical with a small VPS):
  - Server: `mail.reignofplay.com` (or your `mail_myhostname`).
  - Port: **995** (SSL) or **110** (plain; avoid on public Wi‑Fi).
  - Username: full address, e.g. `admin@reignofplay.com`.
  - Password: the one you set in `mail_user_credentials_var` for that address.
- **Delete from VPS after Gmail delivery:**  
  Enable **“Leave a copy of retrieved message on the server”** = **off** (unchecked), or use the option that **deletes messages from the server** after they have been successfully retrieved. That way Gmail confirms delivery, then the message is removed from the VPS so you don’t fill the server and Gmail is the single copy.

Result: new mail arriving at the VPS mailbox is pulled into the Gmail inbox, then deleted from the VPS after retrieval.

### 2. Gmail: “Send mail as” (reply using VPS domain)

- Same **Accounts and Import** tab → **Send mail as** → **Add another email address**.
- Email: `admin@reignofplay.com` (or whichever VPS address you use).
- Uncheck “Treat as an alias” if you want (optional).
- **Next**.
- **Send through SMTP**:
  - SMTP server: `mail.reignofplay.com` (your VPS).
  - Port: **587** (TLS) or **465** (SSL); 587 is usual.
  - Username: `admin@reignofplay.com`.
  - Password: same as for POP3/IMAP.
- **Add account**.
- Gmail will send a verification message to that address; since you’re already fetching it in Gmail, you’ll see the verification email and can confirm.

After this, when you compose or reply in Gmail you can choose “From: admin@reignofplay.com”. Replies will be sent via your VPS and appear as coming from your domain.

---

## Option B: VPS forwards to Gmail (no copy on VPS)

Here the VPS does not keep mail; it forwards every message to Gmail. You still reply as the VPS domain using “Send mail as” (same as Option A step 2).

### 1. Forward incoming mail to Gmail (Postfix)

On the VPS, for each address you want to mirror to Gmail, add a **virtual alias** that delivers to both the local mailbox and the Gmail address (or only to Gmail).

**Option B1 – Local + Gmail (copy on VPS and in Gmail):**

- In Postfix, add a **virtual_alias_maps** entry, e.g.:
  - `admin@reignofplay.com` → `admin@reignofplay.com`, `yourname@gmail.com`
  That usually requires a transport that sends to the local mailbox and also to an external address (e.g. with a custom transport or a local script that forwards). Easiest is to keep local delivery as now and add a **procmail** or **maildrop** rule, or a **sieve** script in Dovecot, that forwards a copy to Gmail.

**Option B2 – Gmail only (no local storage):**

- Make `admin@reignofplay.com` an alias that points only to Gmail:
  - In `/etc/postfix/virtual_alias_maps`:  
    `admin@reignofplay.com  yourname@gmail.com`
- Reload Postfix. Then mail to `admin@reignofplay.com` goes only to Gmail (no mailbox on the VPS).

If you use B2, you don’t need a local mailbox for that address; “reply from VPS domain” is still done in Gmail with “Send mail as” (Option A step 2).

### 2. Reply as VPS domain from Gmail

Same as **Option A, step 2**: add the VPS address in **Send mail as** with SMTP `mail.reignofplay.com`, port 587, and the VPS credentials. Then you can reply from Gmail as `admin@reignofplay.com`.

---

## Summary

| Step | What to do |
|------|------------|
| **Receive / store in Gmail** | Either (A) Gmail “Check mail from other accounts” (POP3/IMAP from VPS), or (B) Postfix/Sieve forward to Gmail. |
| **Delete from VPS after delivery** | In Gmail’s POP3 settings, leave “Leave a copy on the server” **off** so messages are deleted from the VPS after Gmail retrieves them. |
| **Reply as VPS domain** | Gmail “Send mail as” → add `user@yourdomain.com` → SMTP server = VPS (`mail.reignofplay.com`), port 587, same login as mailbox. |

So: **yes**, you can connect the VPS mailbox to a Gmail inbox (fetch or forward) and **reply from the client (Gmail) using your VPS mailbox domain.

---

## Security and deliverability

- Use **TLS/SSL** (ports 995 for POP3, 587 for SMTP) where possible.
- Ensure **SPF** (and ideally **DKIM**) for `reignofplay.com` so replies sent via the VPS are less likely to be marked spam. The playbook does not configure DKIM/SPF; add them (e.g. OpenDKIM + Postfix) and publish the DNS records.
- Gmail “Send mail as” may show “Less secure app” or “App password” if you use 2FA; use an **App Password** for the VPS account if prompted.

---

## Reference (this playbook)

- Mail playbook: `13_setup_mail.yml`
- Mailbox layout: `/var/mail/vhosts/<domain>/<user>/` (Maildir)
- Variables: `mail_domains_var`, `mailboxes_var`, `mail_user_credentials_var`, `mail_myhostname_var`
