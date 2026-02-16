#!/usr/bin/env bash
set -euo pipefail

# Normalize Laravel storage/cache permissions.
# Usage:
#   APP_DIR=/www/wwwroot/vector.533133.xyz APP_USER=www bash scripts/linux/fix_permissions.sh

APP_DIR="${APP_DIR:-/www/wwwroot/vector.533133.xyz}"
APP_USER="${APP_USER:-www}"

if [[ $EUID -ne 0 ]]; then
  echo "Run as root."
  exit 1
fi

chown -R "$APP_USER:$APP_USER" "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
find "$APP_DIR/storage" -type d -exec chmod 775 {} \;
find "$APP_DIR/storage" -type f -exec chmod 664 {} \;

echo "Permissions fixed for $APP_DIR"
