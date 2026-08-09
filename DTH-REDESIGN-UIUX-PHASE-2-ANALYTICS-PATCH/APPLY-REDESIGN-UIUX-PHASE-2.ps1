param(
    [Parameter(Mandatory = $true)]
    [string]$Target
)

$ErrorActionPreference = 'Stop'

$ScriptRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$FilesRoot = Join-Path $ScriptRoot 'files'

if (-not (Test-Path $FilesRoot)) {
    throw "Patch files folder not found: $FilesRoot"
}

if (-not (Test-Path $Target)) {
    throw "Laravel target folder not found: $Target"
}

$Target = (Resolve-Path $Target).Path
$Artisan = Join-Path $Target 'artisan'

if (-not (Test-Path $Artisan)) {
    throw "Target does not look like the Laravel application (artisan not found): $Target"
}

Write-Host '[0/4] Preflight PHP syntax check' -ForegroundColor Cyan
$PhpFiles = Get-ChildItem -Path $FilesRoot -File -Recurse -Filter '*.php'
foreach ($file in $PhpFiles) {
    & php -l $file.FullName | Out-Null
    if ($LASTEXITCODE -ne 0) {
        throw "PHP syntax check failed: $($file.FullName)"
    }
}

$Timestamp = Get-Date -Format 'yyyyMMdd_HHmmss'
$BackupRoot = Join-Path $Target ("_redesign_uiux_phase2_backup_" + $Timestamp)
New-Item -ItemType Directory -Path $BackupRoot -Force | Out-Null
$NewFiles = New-Object System.Collections.Generic.List[string]

Write-Host '[1/4] Backup affected files ->' $BackupRoot -ForegroundColor Cyan
Get-ChildItem -Path $FilesRoot -File -Recurse | ForEach-Object {
    $relative = $_.FullName.Substring($FilesRoot.Length).TrimStart([char]92, [char]47)
    $destination = Join-Path $Target $relative

    if (Test-Path $destination) {
        $backup = Join-Path $BackupRoot $relative
        $backupDir = Split-Path -Parent $backup
        New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
        Copy-Item -Path $destination -Destination $backup -Force
    }
    else {
        $NewFiles.Add($relative)
    }
}

if ($NewFiles.Count -gt 0) {
    $NewFiles | Set-Content -Path (Join-Path $BackupRoot '_NEW_FILES_CREATED_BY_PHASE2.txt') -Encoding UTF8
}

Write-Host '[2/4] Apply Redesign UI/UX Phase 2 files' -ForegroundColor Cyan
Get-ChildItem -Path $FilesRoot -File -Recurse | ForEach-Object {
    $relative = $_.FullName.Substring($FilesRoot.Length).TrimStart([char]92, [char]47)
    $destination = Join-Path $Target $relative
    $destinationDir = Split-Path -Parent $destination
    New-Item -ItemType Directory -Path $destinationDir -Force | Out-Null
    Copy-Item -Path $_.FullName -Destination $destination -Force
}

Write-Host '[3/4] Clear Laravel caches' -ForegroundColor Cyan
Push-Location $Target
try {
    php artisan optimize:clear
    if ($LASTEXITCODE -ne 0) {
        throw 'php artisan optimize:clear failed.'
    }
}
finally {
    Pop-Location
}

Write-Host '[4/4] Completed' -ForegroundColor Cyan
Write-Host ''
Write-Host 'DONE - Redesign UI/UX Phase 2 (Dashboard, Reports & Analytics) applied.' -ForegroundColor Green
Write-Host 'No database migration is required.' -ForegroundColor Green
Write-Host 'No npm build and no queue worker are required for this patch.' -ForegroundColor Green
Write-Host 'Backup:' $BackupRoot -ForegroundColor Yellow
Write-Host ''
Write-Host 'Recommended smoke tests:' -ForegroundColor Yellow
Write-Host '  1. Admin Dashboard'
Write-Host '  2. Marketing Dashboard + Campaign Analysis'
Write-Host '  3. Finance Dashboard + Revenue Analysis'
Write-Host '  4. Sales Dashboard (no Opportunity analytics)'
Write-Host '  5. CSKH Dashboard'
Write-Host '  6. Workforce Analysis'
Write-Host '  7. Email Campaign Report'
