# Linux Ops Scripts

Run all scripts as `root` unless noted.

After upload to server, run:

```bash
chmod +x scripts/linux/*.sh
```

## 0) One-command deploy from GitHub

If the repo is already on server:

```bash
REPO_URL=https://github.com/Raniazcwjyj/vector-binary-decoder.git BRANCH=main \
APP_DIR=/www/wwwroot/vec.456781.xyz \
ENGINE_DIR=/www/wwwroot/vector-binary-decoder-master/python_engine \
QUEUE_NAME=conversions \
bash scripts/linux/deploy_from_github.sh
```

If you want to run directly from GitHub raw URL:

```bash
curl -fsSL https://raw.githubusercontent.com/Raniazcwjyj/vector-binary-decoder/main/scripts/linux/deploy_from_github.sh \
| REPO_URL=https://github.com/Raniazcwjyj/vector-binary-decoder.git BRANCH=main \
  APP_DIR=/www/wwwroot/vec.456781.xyz \
  ENGINE_DIR=/www/wwwroot/vector-binary-decoder-master/python_engine \
  QUEUE_NAME=conversions \
  BT_DOMAIN=vec.456781.xyz \
  bash
```

Notes:

- Script auto-enforces stable `.env` defaults for this stack (`QUEUE_CONNECTION=redis`, `CACHE_STORE=file`, `SESSION_DRIVER=file`).
- Script auto-writes billing defaults (`VECTOR_DECODER_BILLING_ENABLED`, `VECTOR_DECODER_BILLING_ENFORCE_WEB_UPLOAD`, `VECTOR_DECODER_BILLING_CREDIT_COST_PER_TASK`).
- Script auto-installs Composer v2 if missing/incompatible.
- Playwright browsers are installed into `python_engine/.pw-browsers` and verified before service start.
- `BT_DOMAIN` is optional; if set, script auto-fixes BT Nginx root to Laravel `public`.

## 1) One-time install (autostart services)

```bash
bash scripts/linux/install_autostart_services.sh
```

This installs and starts:

- `vector-queue-worker.service`
- `vector-engine.service`
- redis dependency (`redis-server` / `redis` / `redis-local`)

## 2) Restart stack

```bash
bash scripts/linux/restart_stack.sh
```

## 3) Health check

```bash
bash scripts/linux/healthcheck_stack.sh
```

## 4) Requeue pending tasks

```bash
bash scripts/linux/requeue_pending_tasks.sh
```

## 5) Recover missing results from `engine_output`

```bash
bash scripts/linux/recover_missing_results.sh
```

## 6) Fix storage permissions

```bash
bash scripts/linux/fix_permissions.sh
```
