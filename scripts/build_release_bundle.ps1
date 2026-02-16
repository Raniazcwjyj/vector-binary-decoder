param(
    [string]$OutputDir = ".\dist"
)

$ErrorActionPreference = "Stop"

function Write-Step([string]$Message) {
    Write-Host "[bundle] $Message" -ForegroundColor Cyan
}

$repoRoot = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$bundleRoot = Join-Path $repoRoot "tmp_bundle_$timestamp"
$outDirAbs = [System.IO.Path]::GetFullPath((Join-Path $repoRoot $OutputDir))
$zipPath = Join-Path $outDirAbs "vector-release-$timestamp.zip"

if (Test-Path $bundleRoot) { Remove-Item -Recurse -Force $bundleRoot }
New-Item -ItemType Directory -Path $bundleRoot | Out-Null
New-Item -ItemType Directory -Path $outDirAbs -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $bundleRoot "docs") | Out-Null
New-Item -ItemType Directory -Path (Join-Path $bundleRoot "scripts") | Out-Null

Write-Step "Staging files..."
Copy-Item -Recurse -Force (Join-Path $repoRoot "php_api") (Join-Path $bundleRoot "php_api")
Copy-Item -Recurse -Force (Join-Path $repoRoot "python_engine") (Join-Path $bundleRoot "python_engine")
Copy-Item -Recurse -Force (Join-Path $repoRoot "scripts\linux") (Join-Path $bundleRoot "scripts\linux")
Copy-Item -Force (Join-Path $repoRoot "README.md") (Join-Path $bundleRoot "README.md")
Copy-Item -Force (Join-Path $repoRoot "docs\DEPLOYMENT.md") (Join-Path $bundleRoot "docs\DEPLOYMENT.md")
Copy-Item -Force (Join-Path $repoRoot "docs\DEPLOY_RUNBOOK_CN.md") (Join-Path $bundleRoot "docs\DEPLOY_RUNBOOK_CN.md")

Write-Step "Removing runtime garbage..."
$garbage = @(
    (Join-Path $bundleRoot "python_engine\engine_output"),
    (Join-Path $bundleRoot "python_engine\__pycache__"),
    (Join-Path $bundleRoot "php_api\storage"),
    (Join-Path $bundleRoot "php_api\vendor")
)
foreach ($path in $garbage) {
    if (Test-Path $path) {
        Remove-Item -Recurse -Force $path
    }
}
Get-ChildItem -Path $bundleRoot -Recurse -Directory -Filter "__pycache__" -ErrorAction SilentlyContinue | Remove-Item -Recurse -Force

Write-Step "Creating zip: $zipPath"
if (Test-Path $zipPath) { Remove-Item -Force $zipPath }
Compress-Archive -Path (Join-Path $bundleRoot "*") -DestinationPath $zipPath -CompressionLevel Optimal

Write-Step "Cleaning temp..."
Remove-Item -Recurse -Force $bundleRoot

Write-Host ""
Write-Host "Bundle ready: $zipPath" -ForegroundColor Green
