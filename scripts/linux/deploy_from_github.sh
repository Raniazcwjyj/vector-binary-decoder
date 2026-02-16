#!/usr/bin/env bash
set -euo pipefail

# One-command deployment from GitHub.
# Run as root.
#
# Default:
#   REPO_URL=https://github.com/Raniazcwjyj/vector-binary-decoder.git
#
# Optional:
#   BRANCH=main
#   REPO_DIR=/opt/vector-deploy/repo
#   APP_DIR=/www/wwwroot/vector.533133.xyz
#   ENGINE_DIR=/www/wwwroot/vector-binary-decoder-master/python_engine
#   LARAVEL_VERSION=^11.0
#   APP_USER=www
#   ENGINE_USER=root
#   QUEUE_NAME=conversions
#   PHP_FPM_INIT=php-fpm-82
#   RUN_MIGRATE=1
#   BT_DOMAIN=vec.456781.xyz          # optional, auto-fix BT nginx root/rewrite

REPO_URL="${REPO_URL:-https://github.com/Raniazcwjyj/vector-binary-decoder.git}"
BRANCH="${BRANCH:-main}"
REPO_DIR="${REPO_DIR:-/opt/vector-deploy/repo}"
APP_DIR="${APP_DIR:-/www/wwwroot/vector.533133.xyz}"
ENGINE_DIR="${ENGINE_DIR:-/www/wwwroot/vector-binary-decoder-master/python_engine}"
LARAVEL_VERSION="${LARAVEL_VERSION:-^11.0}"
APP_USER="${APP_USER:-www}"
ENGINE_USER="${ENGINE_USER:-root}"
QUEUE_NAME="${QUEUE_NAME:-conversions}"
PHP_FPM_INIT="${PHP_FPM_INIT:-php-fpm-82}"
RUN_MIGRATE="${RUN_MIGRATE:-1}"
BT_DOMAIN="${BT_DOMAIN:-}"

PHP_BIN="$(command -v php || true)"
APP_ENV_FILE=""

if [[ $EUID -ne 0 ]]; then
  echo "Run as root."
  exit 1
fi

if [[ -z "$REPO_URL" ]]; then
  echo "Missing REPO_URL."
  exit 1
fi

if [[ -z "$PHP_BIN" ]]; then
  echo "Command not found: php"
  exit 1
fi

for cmd in git python3 systemctl curl; do
  command -v "$cmd" >/dev/null 2>&1 || { echo "Command not found: $cmd"; exit 1; }
done

sync_overlay() {
  local src="$1"
  local dst="$2"
  shift 2

  mkdir -p "$dst"
  if command -v rsync >/dev/null 2>&1; then
    local args=(-a)
    for item in "$@"; do
      args+=(--exclude "$item")
    done
    rsync "${args[@]}" "$src"/ "$dst"/
  else
    echo "rsync not found, using tar fallback."
    local tar_excludes=()
    for item in "$@"; do
      tar_excludes+=(--exclude="$item")
    done
    tar -C "$src" "${tar_excludes[@]}" -cf - . | tar -C "$dst" -xf -
  fi
}

sync_mirror() {
  local src="$1"
  local dst="$2"
  shift 2

  mkdir -p "$dst"
  if command -v rsync >/dev/null 2>&1; then
    local args=(-a --delete)
    for item in "$@"; do
      args+=(--exclude "$item")
    done
    rsync "${args[@]}" "$src"/ "$dst"/
  else
    echo "rsync not found, mirror fallback uses overlay copy."
    sync_overlay "$src" "$dst" "$@"
  fi
}

overlay_if_exists() {
  local src="$1"
  local dst="$2"
  shift 2
  if [[ -e "$src" ]]; then
    sync_overlay "$src" "$dst" "$@"
  fi
}

set_env() {
  local key="$1"
  local val="$2"
  if grep -qE "^${key}=" "$APP_ENV_FILE"; then
    sed -i "s#^${key}=.*#${key}=${val}#g" "$APP_ENV_FILE"
  else
    printf "%s=%s\n" "$key" "$val" >>"$APP_ENV_FILE"
  fi
}

set_env_if_missing() {
  local key="$1"
  local val="$2"
  if ! grep -qE "^${key}=" "$APP_ENV_FILE"; then
    set_env "$key" "$val"
  fi
}

ensure_composer2() {
  local composer_bin version_out major
  composer_bin="$(command -v composer || true)"
  if [[ -n "$composer_bin" ]]; then
    if version_out="$("$PHP_BIN" -d disable_functions= "$composer_bin" --version 2>/dev/null)"; then
      major="$(echo "$version_out" | sed -n 's/^Composer version \([0-9]\+\).*/\1/p' | head -n1)"
      if [[ "$major" == "2" ]]; then
        echo "$composer_bin"
        return 0
      fi
    fi
  fi

  echo "Installing Composer v2 to /usr/local/bin/composer ..."
  local tmp="/tmp/composer-setup.php"
  "$PHP_BIN" -d disable_functions= -r "copy('https://getcomposer.org/installer', '$tmp');"
  "$PHP_BIN" -d disable_functions= "$tmp" --install-dir=/usr/local/bin --filename=composer
  rm -f "$tmp"

  composer_bin="/usr/local/bin/composer"
  "$PHP_BIN" -d disable_functions= "$composer_bin" --version >/dev/null
  echo "$composer_bin"
}

fix_bt_nginx_if_requested() {
  if [[ -z "$BT_DOMAIN" ]]; then
    return 0
  fi
  local conf="/www/server/panel/vhost/nginx/${BT_DOMAIN}.conf"
  local rewrite="/www/server/panel/vhost/rewrite/${BT_DOMAIN}.conf"
  if [[ ! -f "$conf" ]]; then
    echo "[BT] nginx conf not found: $conf"
    return 0
  fi
  cp -a "$conf" "${conf}.bak.$(date +%F_%H%M%S)"
  sed -i "s#root ${APP_DIR};#root ${APP_DIR}/public;#g" "$conf"
  sed -i "s#root ${APP_DIR}/;#root ${APP_DIR}/public;#g" "$conf"

  if [[ -d "$(dirname "$rewrite")" ]]; then
    cat >"$rewrite" <<'EOF'
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
EOF
  fi

  if command -v nginx >/dev/null 2>&1; then
    nginx -t && nginx -s reload || true
  fi
}

echo "[1/10] Fetch source from GitHub..."
mkdir -p "$(dirname "$REPO_DIR")"
if [[ ! -d "$REPO_DIR/.git" ]]; then
  rm -rf "$REPO_DIR"
  git clone --depth 1 --branch "$BRANCH" "$REPO_URL" "$REPO_DIR"
else
  git -C "$REPO_DIR" fetch origin "$BRANCH" --depth 1
  git -C "$REPO_DIR" checkout "$BRANCH"
  git -C "$REPO_DIR" reset --hard "origin/$BRANCH"
fi

SRC_PHP="$REPO_DIR/php_api"
SRC_ENGINE="$REPO_DIR/python_engine"
if [[ ! -d "$SRC_PHP" ]]; then
  echo "Missing folder in repo: $SRC_PHP"
  exit 1
fi
if [[ ! -d "$SRC_ENGINE" ]]; then
  echo "Missing folder in repo: $SRC_ENGINE"
  exit 1
fi

echo "[2/10] Ensure Composer v2..."
COMPOSER_BIN="$(ensure_composer2)"
COMPOSER_CMD=("$PHP_BIN" -d disable_functions= "$COMPOSER_BIN")

echo "[3/10] Ensure Laravel base project..."
if [[ ! -f "$APP_DIR/artisan" ]]; then
  echo "artisan not found under APP_DIR, creating Laravel base..."
  TMP_BASE="$(mktemp -d /tmp/vector-laravel-base.XXXXXX)"
  "${COMPOSER_CMD[@]}" create-project laravel/laravel "$TMP_BASE" "$LARAVEL_VERSION" --no-interaction
  sync_mirror "$TMP_BASE" "$APP_DIR" .git .github vendor node_modules
  rm -rf "$TMP_BASE"
fi

echo "[4/10] Overlay Laravel module files..."
# Important: only overlay module folders/files.
# Do NOT overwrite Laravel root files like composer.json/artisan/bootstrap.
overlay_if_exists "$SRC_PHP/app" "$APP_DIR/app" .git .github
overlay_if_exists "$SRC_PHP/config" "$APP_DIR/config" .git .github
overlay_if_exists "$SRC_PHP/database/migrations" "$APP_DIR/database/migrations" .git .github
overlay_if_exists "$SRC_PHP/resources/views" "$APP_DIR/resources/views" .git .github
overlay_if_exists "$SRC_PHP/routes" "$APP_DIR/routes" .git .github
overlay_if_exists "$SRC_PHP/tests/Feature" "$APP_DIR/tests/Feature" .git .github

if [[ -f "$SRC_PHP/.env.example" && ! -f "$APP_DIR/.env.example" ]]; then
  cp "$SRC_PHP/.env.example" "$APP_DIR/.env.example"
fi
if [[ ! -f "$APP_DIR/.env" && -f "$APP_DIR/.env.example" ]]; then
  cp "$APP_DIR/.env.example" "$APP_DIR/.env"
fi
if [[ ! -f "$APP_DIR/.env" ]]; then
  echo "Missing .env and .env.example in $APP_DIR"
  exit 1
fi
APP_ENV_FILE="$APP_DIR/.env"

echo "[5/10] Sync engine files..."
ENGINE_EXCLUDES=(.git .github .venv engine_output __pycache__ .pw-browsers)
sync_mirror "$SRC_ENGINE" "$ENGINE_DIR" "${ENGINE_EXCLUDES[@]}"

echo "[6/10] Install runtime dependencies..."
if [[ ! -x "$ENGINE_DIR/.venv/bin/python" ]]; then
  python3 -m venv "$ENGINE_DIR/.venv"
fi
PW_BROWSERS_PATH="$ENGINE_DIR/.pw-browsers"
mkdir -p "$PW_BROWSERS_PATH"
"$ENGINE_DIR/.venv/bin/pip" install --upgrade pip >/dev/null
"$ENGINE_DIR/.venv/bin/pip" install -r "$ENGINE_DIR/requirements.txt"
PLAYWRIGHT_BROWSERS_PATH="$PW_BROWSERS_PATH" "$ENGINE_DIR/.venv/bin/python" -m playwright install-deps chromium || true
PLAYWRIGHT_BROWSERS_PATH="$PW_BROWSERS_PATH" "$ENGINE_DIR/.venv/bin/python" -m playwright install chromium
PLAYWRIGHT_BROWSERS_PATH="$PW_BROWSERS_PATH" "$ENGINE_DIR/.venv/bin/python" -m playwright install --only-shell chromium || true
if ! find "$PW_BROWSERS_PATH" -type f \( -name chrome -o -name chrome-headless-shell \) | grep -q .; then
  echo "Playwright browser binaries not found under $PW_BROWSERS_PATH"
  exit 1
fi

echo "[7/10] Laravel install + env defaults..."
export COMPOSER_ALLOW_SUPERUSER=1
(
  cd "$APP_DIR"
  "${COMPOSER_CMD[@]}" install --no-dev --prefer-dist --no-interaction --optimize-autoloader
)

set_env_if_missing APP_ENV production
set_env_if_missing APP_DEBUG false
set_env CACHE_STORE file
set_env SESSION_DRIVER file
set_env REDIS_HOST 127.0.0.1
set_env REDIS_PORT 6379
set_env QUEUE_CONNECTION redis
set_env REDIS_QUEUE "$QUEUE_NAME"
set_env REDIS_QUEUE_RETRY_AFTER 1200
set_env VECTOR_DECODER_WEB_UI_DEFAULT_HEADLESS true
set_env VECTOR_DECODER_WEB_UI_DEFAULT_MAX_WAIT_SECONDS 240
set_env VECTOR_DECODER_WEB_UI_DEFAULT_IDLE_SECONDS 6
set_env_if_missing VECTOR_DECODER_BILLING_ENABLED true
set_env_if_missing VECTOR_DECODER_BILLING_ENFORCE_WEB_UPLOAD true
set_env_if_missing VECTOR_DECODER_BILLING_CREDIT_COST_PER_TASK 1

TOKEN="$(sed -n 's/^VECTOR_DECODER_ENGINE_INTERNAL_TOKEN=//p' "$APP_ENV_FILE" | head -n1)"
if [[ -z "$TOKEN" ]]; then
  TOKEN="$(python3 - <<'PY'
import secrets
import string
alphabet = string.ascii_letters + string.digits
print(''.join(secrets.choice(alphabet) for _ in range(40)))
PY
)"
  set_env VECTOR_DECODER_ENGINE_INTERNAL_TOKEN "$TOKEN"
fi

chown -R "$APP_USER:$APP_USER" "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" || true
find "$APP_DIR/storage" -type d -exec chmod 775 {} \; || true
find "$APP_DIR/storage" -type f -exec chmod 664 {} \; || true

(
  cd "$APP_DIR"
  php artisan key:generate --force || true
  php artisan optimize:clear
  if [[ "$RUN_MIGRATE" == "1" ]]; then
    php artisan migrate --force
  fi
)

echo "[8/10] Install/update autostart services..."
APP_DIR="$APP_DIR" \
ENGINE_DIR="$ENGINE_DIR" \
APP_USER="$APP_USER" \
ENGINE_USER="$ENGINE_USER" \
QUEUE_NAME="$QUEUE_NAME" \
bash "$REPO_DIR/scripts/linux/install_autostart_services.sh"

echo "[9/10] Restart PHP-FPM + optional BT nginx fix..."
if [[ -x "/etc/init.d/$PHP_FPM_INIT" ]]; then
  "/etc/init.d/$PHP_FPM_INIT" restart || true
fi
fix_bt_nginx_if_requested

echo "[10/10] Final health check..."
APP_DIR="$APP_DIR" bash "$REPO_DIR/scripts/linux/healthcheck_stack.sh"

echo "Deploy done."
