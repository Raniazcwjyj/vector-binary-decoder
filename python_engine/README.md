# Python Engine Service

This service exposes internal API for browser capture + decode.

## Endpoints

- `GET /internal/v1/health`
- `POST /internal/v1/capture-convert`

## Setup

```bash
python -m venv .venv
. .venv/bin/activate  # Windows: .venv\\Scripts\\activate
pip install -r requirements.txt
python -m playwright install chromium
```

## Run

```bash
set INTERNAL_API_TOKEN=change-me
set ENGINE_WORK_DIR=./engine_output
uvicorn engine_api:app --host 0.0.0.0 --port 8001
```

## Request Example

```bash
curl -X POST http://127.0.0.1:8001/internal/v1/capture-convert \
  -H "Content-Type: application/json" \
  -H "X-Internal-Token: change-me" \
  -d '{
    "task_id": "task-123",
    "url": "https://vectorizer.ai/images/xxxx",
    "width": 400,
    "height": 400,
    "channel": "auto",
    "headless": true,
    "max_wait_seconds": 120,
    "idle_seconds": 3
  }'
```

You can also send local image mode (engine host local file path):

```json
{
  "task_id": "task-123",
  "image_path": "/www/wwwroot/site/storage/app/vector-decoder/uploads/a.png",
  "width": 400,
  "height": 400
}
```
