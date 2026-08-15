#!/usr/bin/env bash
# Launch the personal portfolio site locally (PHP built-in server).
# Usage: ./playbooks/local/launch_portfolio.sh [port]
set -euo pipefail

PORT="${1:-8088}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
DOCROOT="${REPO_ROOT}/00_Codebase/portfolio"
URL="http://127.0.0.1:${PORT}/"

if [[ ! -d "${DOCROOT}" ]]; then
  echo "Error: portfolio docroot not found: ${DOCROOT}" >&2
  exit 1
fi

if ! command -v php >/dev/null 2>&1; then
  echo "Error: php is not installed or not on PATH." >&2
  exit 1
fi

if lsof -nP -iTCP:"${PORT}" -sTCP:LISTEN >/dev/null 2>&1; then
  echo "Port ${PORT} is already in use. Stop that process or pass another port:" >&2
  echo "  $0 8090" >&2
  exit 1
fi

echo "Portfolio docroot: ${DOCROOT}"
echo "Open: ${URL}"
echo "Dutch project page: ${URL}content/work/dutch.php"
echo "Press Ctrl+C to stop."
echo

cd "${DOCROOT}"
exec php -S "127.0.0.1:${PORT}"
