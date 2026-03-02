#!/usr/bin/env python3
"""
Update dutch_dashboard.users from playbooks/rop01/templates/.update_users.
Reads users (username, email, role, password); hashes password via PHP container;
INSERTs new users or UPDATEs existing. SSH connection from playbooks/rop01/inventory.ini ([rop01_user] group).

Run from repo root: python3 playbooks/rop01/07_update_dashboard_users.py
"""

from __future__ import annotations

import base64
import os
import subprocess
import sys
from pathlib import Path

try:
    import yaml
except ImportError:
    print("PyYAML required: pip install pyyaml", file=sys.stderr)
    sys.exit(1)

SCRIPT_DIR = Path(__file__).resolve().parent
USERS_FILE = SCRIPT_DIR / "templates" / ".update_users"
INVENTORY_FILE = SCRIPT_DIR / "inventory.ini"
DB_NAME = "dutch_dashboard"
DB_CONTAINER = "rop_website_db"
PHP_CONTAINER = "rop_website_php"


def sh_quote(s: str) -> str:
    """Escape for use inside single-quoted sh string: '...' -> end quote, \"'\", start quote."""
    return "'" + s.replace("'", "'\"'\"'") + "'"


def load_yaml(path: Path) -> dict:
    with open(path, "r", encoding="utf-8") as f:
        return yaml.safe_load(f) or {}


def load_inventory_connection(inventory_path: Path) -> tuple[str, str, str]:
    """Parse inventory.ini for [rop01_user] group; return (host, user, ssh_private_key_file)."""
    text = inventory_path.read_text(encoding="utf-8")
    in_rop01_user = False
    for line in text.splitlines():
        line = line.strip()
        if line.startswith("[") and line.endswith("]"):
            in_rop01_user = line == "[rop01_user]"
            continue
        if not in_rop01_user or not line or line.startswith("#"):
            continue
        # Parse: hostname ansible_host=X ansible_user=Y ansible_ssh_private_key_file=Z
        host = user = key = ""
        for part in line.split():
            if part.startswith("ansible_host="):
                host = part.split("=", 1)[1].strip("'\"")
            elif part.startswith("ansible_user="):
                user = part.split("=", 1)[1].strip("'\"")
            elif part.startswith("ansible_ssh_private_key_file="):
                key = part.split("=", 1)[1].strip("'\"")
        if host and user and key:
            return (host, user, os.path.expanduser(key))
    raise SystemExit(f"No [rop01_user] host with ansible_host, ansible_user, ansible_ssh_private_key_file in {inventory_path}")


def ssh_run(host: str, user: str, key_path: str, command: str, stdin: bytes | None = None) -> subprocess.CompletedProcess:
    key = os.path.expanduser(key_path)
    ssh_cmd = ["ssh", "-o", "StrictHostKeyChecking=accept-new", "-i", key, f"{user}@{host}", command]
    return subprocess.run(ssh_cmd, input=stdin, capture_output=True, timeout=60)


def _run_sql(host: str, user: str, key_path: str, sql: str, silent: bool = False) -> str:
    """Run SQL via docker exec mariadb; return stdout. Same pattern as 06_tasks_run_one_migration.yml: pipe SQL in."""
    sql_b64 = base64.b64encode(sql.encode("utf-8")).decode("ascii")
    flag = " -N" if silent else ""
    # Same as playbook: sg docker -c "echo SQL | docker exec -i ... sh -c 'mariadb -u\$MARIADB_USER -p\$MARIADB_PASSWORD db'"
    cmd = f'sg docker -c "echo {sql_b64} | base64 -d | docker exec -i {DB_CONTAINER} sh -c \'mariadb -u\\$MARIADB_USER -p\\$MARIADB_PASSWORD {DB_NAME}{flag}\'"'
    r = ssh_run(host, user, key_path, cmd)
    if r.returncode != 0:
        err = (r.stderr or b"").decode().strip()
        raise SystemExit(f"MariaDB command failed (exit {r.returncode}): {err}")
    return (r.stdout or b"").decode().strip()


def get_current_row(host: str, user: str, key_path: str, username: str) -> str:
    """Return tab-separated: id, email, role, password_hash or empty if not found."""
    uname_quoted = sh_quote(username)
    sql = f"SELECT id, COALESCE(email,''), COALESCE(role,''), COALESCE(password_hash,'') FROM users WHERE username={uname_quoted} LIMIT 1"
    return _run_sql(host, user, key_path, sql, silent=True)


def hash_password(host: str, user: str, key_path: str, password: str) -> str:
    """Hash password via PHP container; return hash string."""
    php_cmd = "sg docker -c \"docker exec -i " + PHP_CONTAINER + " php -r 'echo password_hash(stream_get_contents(STDIN), PASSWORD_DEFAULT);'\""
    r = ssh_run(host, user, key_path, php_cmd, stdin=password.encode("utf-8"))
    r.check_returncode()
    return (r.stdout or b"").decode().strip()


def run_mariadb(host: str, user: str, key_path: str, sql: str) -> None:
    """Run SQL via docker exec mariadb."""
    _run_sql(host, user, key_path, sql, silent=False)


def main() -> None:
    if not USERS_FILE.exists():
        print(f"Users file not found: {USERS_FILE}", file=sys.stderr)
        sys.exit(1)
    if not INVENTORY_FILE.exists():
        print(f"Inventory not found: {INVENTORY_FILE}", file=sys.stderr)
        sys.exit(1)

    data = load_yaml(USERS_FILE)
    users = data.get("dashboard_users") or []
    if not users:
        print("dashboard_users is empty in", USERS_FILE, file=sys.stderr)
        sys.exit(1)

    host, user, key_path = load_inventory_connection(INVENTORY_FILE)
    if not os.path.isfile(key_path):
        print(f"SSH key file not found: {key_path} (from inventory)", file=sys.stderr)
        sys.exit(1)

    for u in users:
        username = (u.get("username") or "").strip()
        if not username:
            continue
        email = (u.get("email") or "").strip()
        role = (u.get("role") or "").strip()
        password = u.get("password") or ""

        row = get_current_row(host, user, key_path, username)
        password_hash = ""
        if password:
            password_hash = hash_password(host, user, key_path, password)

        if not row:
            # INSERT
            if not password_hash and password:
                password_hash = hash_password(host, user, key_path, password)
            vals = [sh_quote(username), sh_quote(email), sh_quote(role), sh_quote(password_hash)]
            sql = f"INSERT INTO users (username, email, role, password_hash, created_at) VALUES ({vals[0]}, {vals[1]}, {vals[2]}, {vals[3]}, NOW())"
            run_mariadb(host, user, key_path, sql)
            print(f"Inserted user: {username}")
        else:
            # UPDATE only columns that are present in the template and differ (never clear DB data for omitted keys)
            parts = row.split("\t")
            cur_email = (parts[1] if len(parts) > 1 else "").strip()
            cur_role = (parts[2] if len(parts) > 2 else "").strip()
            cur_ph = (parts[3] if len(parts) > 3 else "").strip()
            sets = []
            if "email" in u and (u.get("email") or "").strip() != cur_email:
                sets.append(f"email={sh_quote(email)}")
            if "role" in u and role != cur_role:
                sets.append(f"role={sh_quote(role)}")
            if "password" in u and password and password_hash and password_hash != cur_ph:
                sets.append(f"password_hash={sh_quote(password_hash)}")
            if sets:
                sql = f"UPDATE users SET {', '.join(sets)} WHERE username={sh_quote(username)}"
                run_mariadb(host, user, key_path, sql)
                print(f"Updated user: {username}")
            else:
                print(f"No changes for user: {username}")


if __name__ == "__main__":
    main()
