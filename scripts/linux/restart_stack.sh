#!/usr/bin/env bash
set -euo pipefail

# Restart runtime stack quickly.
# Run as root.

if [[ $EUID -ne 0 ]]; then
  echo "Run as root."
  exit 1
fi

REDIS_UNIT=""
if systemctl list-unit-files | grep -q '^redis-local\.service'; then
  REDIS_UNIT="redis-local"
elif systemctl list-unit-files | grep -q '^redis-server\.service'; then
  REDIS_UNIT="redis-server"
elif systemctl list-unit-files | grep -q '^redis\.service'; then
  REDIS_UNIT="redis"
fi

if [[ -n "$REDIS_UNIT" ]]; then
  systemctl restart "$REDIS_UNIT"
fi
systemctl restart vector-queue-worker
systemctl restart vector-engine

echo "==== status ===="
[[ -n "$REDIS_UNIT" ]] && systemctl is-active "$REDIS_UNIT"
systemctl is-active vector-queue-worker
systemctl is-active vector-engine

echo "==== recent logs ===="
journalctl -u vector-queue-worker -n 20 --no-pager || true
journalctl -u vector-engine -n 20 --no-pager || true
