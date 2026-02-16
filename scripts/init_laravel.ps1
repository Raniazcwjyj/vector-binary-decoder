param(
    [string]$ProjectPath = ".\laravel-vector-api",
    [string]$LaravelVersion = "^11.0",
    [string]$EngineUrl = "http://127.0.0.1:8001",
    [string]$EngineToken = "change-me",
    [string]$StorageDisk = "local",
    [string]$ResultsPrefix = "vector-decoder/results",
    [string]$AllowedHost = "vectorizer.ai",
    [int]$TaskTtlHours = 24,
    [switch]$SkipCreate,
    [switch]$SkipComposerInstall,
    [switch]$SkipMigrate,
    [switch]$CreateApiKey,
    [string]$ApiKeyName = "bootstrap-client",
    [int]$ApiRate = 120
)

$ErrorActionPreference = "Stop"

function Write-Step([string]$Message) {
    Write-Host "[init] $Message" -ForegroundColor Cyan
}

function Test-CommandExists([string]$Name) {
    return [bool](Get-Command $Name -ErrorAction SilentlyContinue)
}

function Ensure-Command([string]$Name) {
    if (-not (Test-CommandExists $Name)) {
        throw "Required command not found: $Name"
    }
}

function Ensure-Directory([string]$Path) {
    if (-not (Test-Path $Path)) {
        New-Item -ItemType Directory -Path $Path | Out-Null
    }
}

function Set-EnvValue([string]$EnvFile, [string]$Key, [string]$Value) {
    $escapedKey = [Regex]::Escape($Key)
    $line = "$Key=$Value"

    if (-not (Test-Path $EnvFile)) {
        Set-Content -Path $EnvFile -Value $line -Encoding UTF8
        return
    }

    $content = Get-Content -Path $EnvFile -Raw
    if ($content -match "(?m)^$escapedKey=") {
        $content = [Regex]::Replace($content, "(?m)^$escapedKey=.*$", $line)
    } else {
        if ($content -notmatch "\r?\n$") {
            $content += [Environment]::NewLine
        }
        $content += $line + [Environment]::NewLine
    }
    Set-Content -Path $EnvFile -Value $content -Encoding UTF8
}

function Ensure-ContainsLine([string]$FilePath, [string]$Line) {
    if (-not (Test-Path $FilePath)) {
        Set-Content -Path $FilePath -Value "<?php`n`n$Line`n" -Encoding UTF8
        return
    }

    $content = Get-Content -Path $FilePath -Raw
    if ($content -notmatch [Regex]::Escape($Line)) {
        if ($content -notmatch "\r?\n$") {
            $content += [Environment]::NewLine
        }
        $content += $Line + [Environment]::NewLine
        Set-Content -Path $FilePath -Value $content -Encoding UTF8
    }
}

function Ensure-PHPUseStatement([string]$FilePath, [string]$UseLine) {
    if (-not (Test-Path $FilePath)) {
        Set-Content -Path $FilePath -Value "<?php`n`n$UseLine`n" -Encoding UTF8
        return
    }

    $content = Get-Content -Path $FilePath -Raw
    if ($content -match [Regex]::Escape($UseLine)) {
        return
    }

    $normalized = $content -replace "`r`n", "`n"
    if ($normalized -match "^<\?php\n") {
        $normalized = $normalized -replace "^<\?php\n", "<?php`n`n$UseLine`n"
    } else {
        $normalized = "<?php`n`n$UseLine`n`n$normalized"
    }
    Set-Content -Path $FilePath -Value $normalized -Encoding UTF8
}

function Copy-Tree([string]$From, [string]$To) {
    Ensure-Directory $To
    Copy-Item -Path (Join-Path $From "*") -Destination $To -Recurse -Force
}

$repoRoot = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
$moduleRoot = Join-Path $repoRoot "php_api"
$targetPath = [System.IO.Path]::GetFullPath($ProjectPath)

Write-Step "Repository root: $repoRoot"
Write-Step "Target Laravel path: $targetPath"

Ensure-Command "php"
Ensure-Command "composer"

if (-not (Test-Path $moduleRoot)) {
    throw "php_api folder not found at: $moduleRoot"
}

if (-not $SkipCreate) {
    if (-not (Test-Path $targetPath)) {
        Write-Step "Creating Laravel project ($LaravelVersion)..."
        composer create-project laravel/laravel $targetPath $LaravelVersion
    } else {
        Write-Step "Laravel path exists, skip create."
    }
}

if (-not (Test-Path (Join-Path $targetPath "artisan"))) {
    throw "Target is not a Laravel project (artisan not found): $targetPath"
}

Write-Step "Copying module files..."
Copy-Tree (Join-Path $moduleRoot "app") (Join-Path $targetPath "app")
Copy-Tree (Join-Path $moduleRoot "config") (Join-Path $targetPath "config")
Copy-Tree (Join-Path $moduleRoot "database\migrations") (Join-Path $targetPath "database\migrations")
Copy-Tree (Join-Path $moduleRoot "tests\Feature") (Join-Path $targetPath "tests\Feature")
Copy-Tree (Join-Path $moduleRoot "resources\views") (Join-Path $targetPath "resources\views")

Ensure-Directory (Join-Path $targetPath "routes")
Copy-Item -Path (Join-Path $moduleRoot "routes\vector_decoder.php") -Destination (Join-Path $targetPath "routes\vector_decoder.php") -Force
Copy-Item -Path (Join-Path $moduleRoot "routes\vector_decoder_web.php") -Destination (Join-Path $targetPath "routes\vector_decoder_web.php") -Force

$apiRoutes = Join-Path $targetPath "routes\api.php"
$requireLine = "require __DIR__ . '/vector_decoder.php';"
Ensure-ContainsLine $apiRoutes $requireLine
$webRoutes = Join-Path $targetPath "routes\web.php"
$webRequireLine = "require __DIR__ . '/vector_decoder_web.php';"
Ensure-ContainsLine $webRoutes $webRequireLine

$consoleRoutes = Join-Path $targetPath "routes\console.php"
Ensure-PHPUseStatement $consoleRoutes "use Illuminate\Support\Facades\Schedule;"
Ensure-ContainsLine $consoleRoutes "Schedule::command('vector-decoder:cleanup-expired')->hourly();"

Write-Step "Preparing .env..."
$envFile = Join-Path $targetPath ".env"
$envExample = Join-Path $targetPath ".env.example"
if (-not (Test-Path $envFile) -and (Test-Path $envExample)) {
    Copy-Item -Path $envExample -Destination $envFile -Force
}

Set-EnvValue $envFile "QUEUE_CONNECTION" "redis"
Set-EnvValue $envFile "CACHE_STORE" "redis"
Set-EnvValue $envFile "VECTOR_DECODER_ENGINE_URL" $EngineUrl
Set-EnvValue $envFile "VECTOR_DECODER_ENGINE_INTERNAL_TOKEN" $EngineToken
Set-EnvValue $envFile "VECTOR_DECODER_STORAGE_DISK" $StorageDisk
Set-EnvValue $envFile "VECTOR_DECODER_RESULTS_PREFIX" $ResultsPrefix
Set-EnvValue $envFile "VECTOR_DECODER_ALLOWED_HOST" $AllowedHost
Set-EnvValue $envFile "VECTOR_DECODER_TASK_TTL_HOURS" "$TaskTtlHours"
Set-EnvValue $envFile "VECTOR_DECODER_WEB_UI_API_KEY_NAME" "web-ui"
Set-EnvValue $envFile "VECTOR_DECODER_WEB_UI_UPLOAD_MAX_KB" "10240"

Push-Location $targetPath
try {
    if (-not $SkipComposerInstall) {
        Write-Step "Running composer install..."
        composer install
    }

    Write-Step "Clearing config cache..."
    php artisan config:clear

    if (-not $SkipMigrate) {
        Write-Step "Running migrations..."
        php artisan migrate --force
    } else {
        Write-Step "Skip migrations by flag."
    }

    if ($CreateApiKey) {
        Write-Step "Creating API key..."
        php artisan vector-decoder:create-api-key $ApiKeyName --rate=$ApiRate
    }
}
finally {
    Pop-Location
}

Write-Host ""
Write-Host "Laravel init complete." -ForegroundColor Green
Write-Host "Next steps:"
Write-Host "1) Start Python engine:"
Write-Host "   cd python_engine"
Write-Host "   pip install -r requirements.txt"
Write-Host "   python -m playwright install chromium"
Write-Host "   uvicorn engine_api:app --host 0.0.0.0 --port 8001"
Write-Host "2) Start Laravel queue worker:"
Write-Host "   cd $targetPath"
Write-Host "   php artisan queue:work --queue=conversions --tries=3 --timeout=190"
