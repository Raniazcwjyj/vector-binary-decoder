# Deployment Guide

This document is the single source of truth for production deployment.

## 1) What to deploy

Server paths used in this project:

- Laravel app root: `/www/wwwroot/vector.533133.xyz`
- Python engine root: `/www/wwwroot/vector-binary-decoder-master/python_engine`

From this repository, deploy these folders:

- `php_api/` -> merge into Laravel app root
- `python_engine/` -> copy to engine root
- `scripts/linux/` -> optional but recommended for operations

## 2) Runtime dependencies

- PHP 8.2+
- MySQL
- Redis
- Python 3.10+ with virtualenv
- Playwright chromium runtime
- `xvfb-run`

## 3) Initial setup on server

### Python engine

```bash
cd /www/wwwroot/vector-binary-decoder-master/python_engine
python3 -m venv .venv
. .venv/bin/activate
pip install -r requirements.txt
python -m playwright install chromium
```

### Laravel side

```bash
cd /www/wwwroot/vector.533133.xyz
cp .env.example .env   # if .env does not exist
php artisan migrate --force
php artisan optimize:clear
```

Ensure `.env` includes correct values (DB/Redis/engine token/url).

## 3.5) One-command deploy from GitHub

Run on server as root:

```bash
REPO_URL=https://github.com/Raniazcwjyj/vector-binary-decoder.git BRANCH=main bash scripts/linux/deploy_from_github.sh
```

Or run without pre-uploading scripts:

```bash
curl -fsSL https://raw.githubusercontent.com/Raniazcwjyj/vector-binary-decoder/main/scripts/linux/deploy_from_github.sh \
| REPO_URL=https://github.com/Raniazcwjyj/vector-binary-decoder.git BRANCH=main bash
```

The script performs:

- pull latest source from GitHub
- sync `php_api` and `python_engine`
- install python deps + playwright chromium
- run `composer install` (if composer exists)
- run `php artisan optimize:clear` and migrations
- install/update systemd services and restart stack
- print final healthcheck

## 4) Install autostart services (recommended)

Use the bundled script:

```bash
cd <repo-root>
bash scripts/linux/install_autostart_services.sh
```

This installs and starts:

- redis dependency service
- `vector-queue-worker.service`
- `vector-engine.service`

## 5) Verify after deployment/reboot

```bash
bash scripts/linux/healthcheck_stack.sh
```

Expected:

- service status is `active`
- Redis `PONG`
- engine health returns `{"ok":true,...}`

## 6) Common recovery commands

Restart stack:

```bash
bash scripts/linux/restart_stack.sh
```

Requeue tasks left queued while Redis was unavailable:

```bash
bash scripts/linux/requeue_pending_tasks.sh
```

Recover missing result files from engine output:

```bash
bash scripts/linux/recover_missing_results.sh
```

Fix storage permissions:

```bash
bash scripts/linux/fix_permissions.sh
```
