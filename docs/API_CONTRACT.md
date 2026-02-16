# API Contract

## Public API (Laravel)

Web upload entry:

- `GET /vector`

### `POST /api/v1/tasks`

Headers:

- `X-API-Key: <plain-key>`

Body:

```json
{
  "url": "https://vectorizer.ai/",
  "width": 400,
  "height": 400,
  "channel": "auto",
  "headless": true,
  "max_wait_seconds": 120,
  "idle_seconds": 3
}
```

`/api/v1/tasks` remains URL mode.
Browser upload mode is provided by web page `/vector`.

Response `202`:

```json
{
  "ok": true,
  "task_id": "uuid",
  "status": "queued"
}
```

### `GET /api/v1/tasks/{task_id}`

Response `200`:

```json
{
  "ok": true,
  "task_id": "uuid",
  "status": "running",
  "progress": 60,
  "error_code": null,
  "error_message": null,
  "meta": null
}
```

### `GET /api/v1/tasks/{task_id}/result`

- default: `image/svg+xml`
- query `?format=json`: JSON payload

### `GET /api/v1/health`

Returns php/mysql/redis/python-engine health.

## Internal API (Python Engine)

### `POST /internal/v1/capture-convert`

Headers:

- `X-Internal-Token: <token>`

Body:

```json
{
  "task_id": "uuid",
  "url": "https://vectorizer.ai/",
  "width": 400,
  "height": 400,
  "channel": "auto",
  "headless": true,
  "max_wait_seconds": 120,
  "idle_seconds": 3,
  "verbose": false
}
```

Internal API also supports upload mode:

```json
{
  "task_id": "uuid",
  "image_path": "/abs/path/to/image.png",
  "width": 400,
  "height": 400
}
```

Success:

```json
{
  "ok": true,
  "task_id": "uuid",
  "output_file": ".../output.svg",
  "svg": "<svg ...>",
  "meta": {
    "chunks_used": 3,
    "chunks_total": 3,
    "duplicates_skipped": 2,
    "shapes": 10,
    "loops": 20,
    "interfaces": 5,
    "palette_colors": 4,
    "elapsed_ms": 22340
  }
}
```

Failure:

```json
{
  "ok": false,
  "error_code": "E_TIMEOUT",
  "message": "Capture timeout after 120s.",
  "details": "palette_found=false, chunks_found=0"
}
```

## Error Codes

- `E_AUTH`
- `E_PARAM`
- `E_TIMEOUT`
- `E_BROWSER_LAUNCH`
- `E_NO_PALETTE`
- `E_NO_CHUNK`
- `E_ENGINE`
- `E_INTERNAL`
