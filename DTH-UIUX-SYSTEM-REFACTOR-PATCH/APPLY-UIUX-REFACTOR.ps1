param(
    [Parameter(Mandatory = $true)]
    [string]$Target,

    [switch]$SkipMigrate
)

$ErrorActionPreference = 'Stop'
$Target = [System.IO.Path]::GetFullPath($Target)
$FilesRoot = Join-Path $PSScriptRoot 'files'

if (-not (Test-Path $FilesRoot)) {
    throw "Patch files folder not found: $FilesRoot"
}
if (-not (Test-Path (Join-Path $Target 'artisan'))) {
    throw "Target is not the Laravel application root (artisan not found): $Target"
}

$timestamp = Get-Date -Format 'yyyyMMdd_HHmmss'
$BackupRoot = Join-Path $Target "_uiux_refactor_backup_$timestamp"
New-Item -ItemType Directory -Force -Path $BackupRoot | Out-Null
$patchFiles = Get-ChildItem -Path $FilesRoot -Recurse -File

Write-Host "[1/4] Backup existing files -> $BackupRoot" -ForegroundColor Cyan
foreach ($file in $patchFiles) {
    $relativePath = $file.FullName.Substring($FilesRoot.Length)
    $relativePath = $relativePath.TrimStart(
        [System.IO.Path]::DirectorySeparatorChar,
        [System.IO.Path]::AltDirectorySeparatorChar
    )
    $destination = Join-Path $Target $relativePath
    if (Test-Path $destination) {
        $backup = Join-Path $BackupRoot $relativePath
        New-Item -ItemType Directory -Force -Path (Split-Path $backup -Parent) | Out-Null
        Copy-Item -Path $destination -Destination $backup -Force
    }
}

Write-Host "[2/4] Copy UI/UX patch into project" -ForegroundColor Cyan
foreach ($file in $patchFiles) {
    $relativePath = $file.FullName.Substring($FilesRoot.Length)
    $relativePath = $relativePath.TrimStart(
        [System.IO.Path]::DirectorySeparatorChar,
        [System.IO.Path]::AltDirectorySeparatorChar
    )
    $destination = Join-Path $Target $relativePath
    New-Item -ItemType Directory -Force -Path (Split-Path $destination -Parent) | Out-Null
    Copy-Item -Path $file.FullName -Destination $destination -Force
}

Push-Location $Target
try {
    if (-not $SkipMigrate) {
        Write-Host "[3/4] Run Laravel migrations" -ForegroundColor Cyan
        & php artisan migrate --force
        if ($LASTEXITCODE -ne 0) { throw "php artisan migrate --force failed with exit code $LASTEXITCODE" }
    } else {
        Write-Host "[3/4] Skip migrations as requested" -ForegroundColor Yellow
    }

    Write-Host "[4/4] Clear Laravel caches" -ForegroundColor Cyan
    & php artisan optimize:clear
    if ($LASTEXITCODE -ne 0) { throw "php artisan optimize:clear failed with exit code $LASTEXITCODE" }
}
finally {
    Pop-Location
}

Write-Host ""
Write-Host "DONE - UI/UX refactor applied successfully." -ForegroundColor Green
Write-Host "Backup: $BackupRoot" -ForegroundColor DarkGray
Write-Host "No npm build and no queue worker are required for this UI/UX patch." -ForegroundColor DarkGray
