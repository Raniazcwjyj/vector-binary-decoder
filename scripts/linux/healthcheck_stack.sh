#!/usr/bin/env bash
set -euo pipefail

# Quick runtime healthcheck.
# Usage:
#   APP_DIR=/www/wwwroot/vector.533133.xyz bash scripts/linux/healthcheck_stack.sh

APP_DIR="${APP_DIR:-/www/wwwroot/vector.533133.xyz}"
ENGINE_DIR="${ENGINE_DIR:-/www/wwwroot/vector-binary-decoder-master/python_engine}"

echo "== systemd status =="
for svc in redis-local redis-server redis vector-queue-worker vector-engine; do
  if systemctl list-unit-files | grep -q "^${svc}\.service"; then
    printf "%-22s %s\n" "${svc}" "$(systemctl is-active "$svc" || true)"
  fi
done

echo
echo "== process check =="
ps -eo user,pid,cmd | grep -E "artisan queue:work|uvicorn engine_api:app|redis-server" | grep -v grep || true

echo
echo "== redis ping =="
redis-cli -h 127.0.0.1 -p 6379 ping || true

echo
echo "== engine health =="
if [[ -f "$APP_DIR/.env" ]]; then
  TOKEN="$(sed -n 's/^VECTOR_DECODER_ENGINE_INTERNAL_TOKEN=//p' "$APP_DIR/.env" | head -n1)"
  if [[ -n "$TOKEN" ]]; then
    curl -s -H "X-Internal-Token: $TOKEN" http://127.0.0.1:8001/internal/v1/health || true
    echo
  else
    echo "token not found in $APP_DIR/.env"
  fi
else
  echo ".env not found: $APP_DIR/.env"
fi

echo
echo "== playwright binaries =="
find "$ENGINE_DIR/.pw-browsers" -type f \( -name chrome -o -name chrome-headless-shell \) 2>/dev/null | sed -n '1,6p' || true

echo
echo "== latest worker log =="
tail -n 40 "$APP_DIR/storage/logs/queue-worker.log" 2>/dev/null || true
