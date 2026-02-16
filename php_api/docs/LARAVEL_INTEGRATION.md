# Laravel Integration Notes

## 0) One-command initializer (Recommended)

From repository root:

```powershell
.\init_laravel.bat -ProjectPath .\laravel-vector-api -CreateApiKey
```

Or run script directly:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\init_laravel.ps1 -ProjectPath .\laravel-vector-api -CreateApiKey
```

Common flags:

- `-SkipCreate` (use existing Laravel project)
- `-SkipComposerInstall`
- `-SkipMigrate`
- `-EngineUrl http://127.0.0.1:8001`
- `-EngineToken change-me`

The initializer also copies and enables:

- API routes (`/api/v1/*`)
- Web upload UI (`/vector`)

## 1) Register Middleware Alias

Not required in this module version.
Routes use middleware class directly: `ApiKeyMiddleware::class`.

## 2) Register Artisan Commands

In Laravel 11, commands under `app/Console/Commands` are auto-discovered.
The initializer appends cleanup schedule to `routes/console.php`:

- `Schedule::command('vector-decoder:cleanup-expired')->hourly();`

## 3) Queue Worker

```bash
php artisan queue:work --queue=conversions --tries=3 --timeout=190
```

## 4) API Key Creation

```bash
php artisan vector-decoder:create-api-key partner-a --rate=120
```

## 5) Result Storage

Default disk is `local`. For S3, set:

```env
VECTOR_DECODER_STORAGE_DISK=s3
```

## 6) Web Upload Page

After integration, open:

```text
https://your-domain/vector
```

This page lets users upload an image directly. Backend will auto submit to Vectorizer, capture websocket payload, and produce SVG.
