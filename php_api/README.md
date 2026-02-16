# PHP API Gateway (Laravel 11 Target)

This folder contains a Laravel-oriented implementation for:

- API key authentication (`X-API-Key`)
- async task submission/query/result APIs
- queue job that calls Python engine service
- task metadata persistence

## Endpoints

- `POST /api/v1/tasks`
- `GET /api/v1/tasks/{task_id}`
- `GET /api/v1/tasks/{task_id}/result`
- `GET /api/v1/health`

## Web Upload UI

- `GET /vector` image upload page
- `POST /vector/upload` submit conversion task
- `GET /vector/tasks/{task_id}` task status page (auto polling)
- `GET /vector/tasks/{task_id}/result` inline/open/download SVG

## Required Environment

- PHP 8.2+
- Laravel 11
- MySQL
- Redis (queue + cache)
- Python engine service (from `../python_engine`)

## Quick Integration

Fast path:

```powershell
.\init_laravel.bat -ProjectPath .\laravel-vector-api -CreateApiKey
```

Manual path:

1. Copy `app/`, `resources/views/`, `routes/`, `database/migrations/`, `config/vector_decoder.php` into your Laravel app.
2. Include `routes/vector_decoder.php` from your `routes/api.php`.
3. Include `routes/vector_decoder_web.php` from your `routes/web.php`.
4. Add env values from `.env.example`.
5. Run migrations:
   - `php artisan migrate`
6. Start queue worker:
   - `php artisan queue:work --queue=conversions --tries=2 --timeout=960 --sleep=1`

For production, do not run worker manually long-term. Use systemd via:

- `scripts/linux/install_autostart_services.sh`
- `scripts/linux/healthcheck_stack.sh`

## Behavior

- URL whitelist only allows `https://*.vectorizer.ai/...`
- requests are authenticated by API key
- tasks are queued (`queued -> running -> succeeded/failed`)
- result SVG is stored locally by default
