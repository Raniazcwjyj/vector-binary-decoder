#!/usr/bin/env bash
set -euo pipefail

# One-time bootstrap for production autostart.
# Run as root.
#
# Optional overrides:
#   APP_DIR=/www/wwwroot/vector.533133.xyz
#   ENGINE_DIR=/www/wwwroot/vector-binary-decoder-master/python_engine
#   APP_USER=www
#   ENGINE_USER=root
#   QUEUE_NAME=conversions

APP_DIR="${APP_DIR:-/www/wwwroot/vector.533133.xyz}"
ENGINE_DIR="${ENGINE_DIR:-/www/wwwroot/vector-binary-decoder-master/python_engine}"
APP_USER="${APP_USER:-www}"
ENGINE_USER="${ENGINE_USER:-root}"
QUEUE_NAME="${QUEUE_NAME:-conversions}"

if [[ $EUID -ne 0 ]]; then
  echo "Run as root."
  exit 1
fi

if [[ ! -d "$APP_DIR" ]]; then
  echo "APP_DIR not found: $APP_DIR"
  exit 1
fi
if [[ ! -d "$ENGINE_DIR" ]]; then
  echo "ENGINE_DIR not found: $ENGINE_DIR"
  exit 1
fi

PHP_BIN="$(command -v php || true)"
XVFB_BIN="$(command -v xvfb-run || true)"
if [[ -z "$PHP_BIN" ]]; then
  echo "php not found in PATH."
  exit 1
fi
if [[ -z "$XVFB_BIN" ]]; then
  echo "xvfb-run not found in PATH."
  exit 1
fi

id "$APP_USER" >/dev/null 2>&1 || { echo "User not found: $APP_USER"; exit 1; }
id "$ENGINE_USER" >/dev/null 2>&1 || { echo "User not found: $ENGINE_USER"; exit 1; }

REDIS_UNIT=""
if systemctl list-unit-files | grep -q '^redis-server\.service'; then
  if systemctl restart redis-server >/dev/null 2>&1; then
    REDIS_UNIT="redis-server.service"
  fi
fi
if [[ -z "$REDIS_UNIT" ]] && systemctl list-unit-files | grep -q '^redis\.service'; then
  if systemctl restart redis >/dev/null 2>&1; then
    REDIS_UNIT="redis.service"
  fi
fi
if [[ -z "$REDIS_UNIT" ]]; then
  if [[ ! -x /etc/init.d/redis ]]; then
    echo "No working redis unit and /etc/init.d/redis is missing."
    exit 1
  fi
  cat >/etc/systemd/system/redis-local.service <<'EOF'
[Unit]
Description=Redis via /etc/init.d/redis
After=network.target

[Service]
Type=forking
ExecStart=/etc/init.d/redis start
ExecStop=/etc/init.d/redis stop
ExecReload=/etc/init.d/redis restart
RemainAfterExit=yes
TimeoutSec=30

[Install]
WantedBy=multi-user.target
EOF
  REDIS_UNIT="redis-local.service"
fi

cat >/usr/local/bin/vector-queue-start.sh <<EOF
#!/usr/bin/env bash
set -euo pipefail
cd "$APP_DIR"
# BT/PHP often disables pcntl_* via disable_functions; clear it for worker process.
exec "$PHP_BIN" -d disable_functions= artisan queue:work --queue="$QUEUE_NAME" --tries=2 --timeout=960 --sleep=1 --no-interaction >> "$APP_DIR/storage/logs/queue-worker.log" 2>&1
EOF
chmod +x /usr/local/bin/vector-queue-start.sh

cat >/usr/local/bin/vector-engine-start.sh <<EOF
#!/usr/bin/env bash
set -euo pipefail
APP_DIR="$APP_DIR"
ENGINE_DIR="$ENGINE_DIR"
PW_DIR="\$ENGINE_DIR/.pw-browsers"
TOKEN="\$(sed -n 's/^VECTOR_DECODER_ENGINE_INTERNAL_TOKEN=//p' "\$APP_DIR/.env" | head -n1)"
if [[ -z "\$TOKEN" ]]; then
  echo "VECTOR_DECODER_ENGINE_INTERNAL_TOKEN is empty"
  exit 1
fi
mkdir -p "\$PW_DIR"
if ! find "\$PW_DIR" -type f \( -name chrome -o -name chrome-headless-shell \) | grep -q .; then
  echo "Playwright browser binaries missing in \$PW_DIR"
  echo "Run: PLAYWRIGHT_BROWSERS_PATH=\$PW_DIR \$ENGINE_DIR/.venv/bin/python -m playwright install chromium"
  exit 1
fi
cd "\$ENGINE_DIR"
exec env PLAYWRIGHT_BROWSERS_PATH="\$PW_DIR" INTERNAL_API_TOKEN="\$TOKEN" ENGINE_WORK_DIR="\$ENGINE_DIR/engine_output" \
"$XVFB_BIN" -a -s "-screen 0 1920x1080x24" \
"\$ENGINE_DIR/.venv/bin/python" -m uvicorn engine_api:app --host 127.0.0.1 --port 8001 --log-level info \
>> "\$ENGINE_DIR/engine.log" 2>&1
EOF
chmod +x /usr/local/bin/vector-engine-start.sh

cat >/etc/systemd/system/vector-queue-worker.service <<EOF
[Unit]
Description=Vector Queue Worker
After=network.target $REDIS_UNIT
Requires=$REDIS_UNIT

[Service]
Type=simple
User=$APP_USER
Group=$APP_USER
WorkingDirectory=$APP_DIR
ExecStart=/usr/local/bin/vector-queue-start.sh
Restart=always
RestartSec=3
KillSignal=SIGTERM
TimeoutStopSec=30

[Install]
WantedBy=multi-user.target
EOF

cat >/etc/systemd/system/vector-engine.service <<EOF
[Unit]
Description=Vector Internal Engine
After=network.target
Wants=network.target

[Service]
Type=simple
User=$ENGINE_USER
WorkingDirectory=$ENGINE_DIR
ExecStart=/usr/local/bin/vector-engine-start.sh
Restart=always
RestartSec=3
KillSignal=SIGTERM
TimeoutStopSec=30

[Install]
WantedBy=multi-user.target
EOF

pkill -f "artisan queue:work" || true
pkill -f "uvicorn engine_api:app" || true

systemctl daemon-reload
if [[ "$REDIS_UNIT" == "redis-local.service" ]]; then
  systemctl disable redis-server >/dev/null 2>&1 || true
  systemctl stop redis-server >/dev/null 2>&1 || true
  systemctl reset-failed redis-server >/dev/null 2>&1 || true
fi
systemctl enable --now "$REDIS_UNIT"
systemctl enable --now vector-queue-worker
systemctl enable --now vector-engine

echo "==== status ===="
systemctl is-active "$REDIS_UNIT"
systemctl is-active vector-queue-worker
systemctl is-active vector-engine

echo "==== processes ===="
ps -eo user,pid,cmd | grep -E "artisan queue:work|uvicorn engine_api:app" | grep -v grep || true

echo "==== redis ===="
redis-cli -h 127.0.0.1 -p 6379 ping

echo "==== engine health ===="
curl -s -H "X-Internal-Token: $(sed -n 's/^VECTOR_DECODER_ENGINE_INTERNAL_TOKEN=//p' "$APP_DIR/.env" | head -n1)" \
http://127.0.0.1:8001/internal/v1/health && echo

echo "Autostart install done."
