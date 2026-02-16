# Vector Binary Decoder

This repository includes:

- Python internal engine API: `python_engine/`
- Laravel gateway + web upload UI: `php_api/`
- Linux ops scripts (autostart, restart, healthcheck): `scripts/linux/`

## Production mode

Read these first:

- `docs/DEPLOY_RUNBOOK_CN.md` (recommended, Chinese)
- `docs/DEPLOYMENT.md`
- `scripts/linux/README.md`

One-time autostart install on Linux server:

```bash
bash scripts/linux/install_autostart_services.sh
```

One-command deploy from GitHub on Linux server:

```bash
REPO_URL=https://github.com/Raniazcwjyj/vector-binary-decoder.git BRANCH=main \
APP_DIR=/www/wwwroot/vec.456781.xyz \
ENGINE_DIR=/www/wwwroot/vector-binary-decoder-master/python_engine \
QUEUE_NAME=conversions \
BT_DOMAIN=vec.456781.xyz \
bash scripts/linux/deploy_from_github.sh
```

Daily operations:

```bash
bash scripts/linux/healthcheck_stack.sh
bash scripts/linux/restart_stack.sh
```

Build a clean deployment zip from local files:

```powershell
.\scripts\build_release_bundle.ps1
```

## Key endpoints

Public (Laravel):

- `GET /vector`
- `POST /vector/upload`
- `GET /vector/billing`
- `POST /vector/billing/redeem`
- `POST /vector/billing/bind`
- `GET /vector/tasks/{task_id}`
- `GET /vector/tasks/{task_id}/result`
- `POST /api/v1/tasks`
- `GET /api/v1/tasks/{task_id}`
- `GET /api/v1/tasks/{task_id}/result`
- `GET /api/v1/health`

Internal (Python engine):

- `GET /internal/v1/health`
- `POST /internal/v1/capture-convert`

## Billing (card code)

Default billing is enabled for web upload:

- `VECTOR_DECODER_BILLING_ENABLED=true`
- `VECTOR_DECODER_BILLING_ENFORCE_WEB_UPLOAD=true`
- `VECTOR_DECODER_BILLING_CREDIT_COST_PER_TASK=1`

Generate card codes:

```bash
php artisan vector-decoder:generate-redeem-codes 2026Q1 50 --days=30 --credits=100 --prefix=VIP
```
