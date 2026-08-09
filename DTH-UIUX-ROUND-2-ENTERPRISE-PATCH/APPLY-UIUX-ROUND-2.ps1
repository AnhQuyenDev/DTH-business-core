param(
    [Parameter(Mandatory = $true)]
    [string]$Target
)

$ErrorActionPreference = 'Stop'

$PatchRoot = $PSScriptRoot
$FilesRoot = Join-Path $PatchRoot 'files'
$TargetPath = [System.IO.Path]::GetFullPath((Join-Path (Get-Location) $Target))

if (-not (Test-Path (Join-Path $TargetPath 'artisan'))) {
    throw "Target is not the Laravel application directory: $TargetPath"
}

if (-not (Test-Path $FilesRoot)) {
    throw "Patch files directory not found: $FilesRoot"
}

$Timestamp = Get-Date -Format 'yyyyMMdd_HHmmss'
$BackupRoot = Join-Path $TargetPath ("_uiux_round2_backup_" + $Timestamp)
New-Item -ItemType Directory -Path $BackupRoot -Force | Out-Null

Write-Host "[1/3] Backup current files -> $BackupRoot" -ForegroundColor Cyan
$Files = Get-ChildItem -Path $FilesRoot -Recurse -File
foreach ($File in $Files) {
    $Relative = $File.FullName.Substring($FilesRoot.Length).TrimStart([char]92, [char]47)
    $Destination = Join-Path $TargetPath $Relative

    if (Test-Path $Destination) {
        $BackupDestination = Join-Path $BackupRoot $Relative
        $BackupDirectory = Split-Path -Parent $BackupDestination
        if ($BackupDirectory) {
            New-Item -ItemType Directory -Path $BackupDirectory -Force | Out-Null
        }
        Copy-Item -Path $Destination -Destination $BackupDestination -Force
    }
}

Write-Host "[2/3] Copy UI/UX Round 2 files" -ForegroundColor Cyan
foreach ($File in $Files) {
    $Relative = $File.FullName.Substring($FilesRoot.Length).TrimStart([char]92, [char]47)
    $Destination = Join-Path $TargetPath $Relative
    $DestinationDirectory = Split-Path -Parent $Destination
    if ($DestinationDirectory) {
        New-Item -ItemType Directory -Path $DestinationDirectory -Force | Out-Null
    }
    Copy-Item -Path $File.FullName -Destination $Destination -Force
}

Write-Host "[3/3] Clear Laravel caches" -ForegroundColor Cyan
Push-Location $TargetPath
try {
    & php artisan optimize:clear
    if ($LASTEXITCODE -ne 0) {
        throw "php artisan optimize:clear failed with exit code $LASTEXITCODE"
    }
}
finally {
    Pop-Location
}

Write-Host "DONE - UI/UX Round 2 applied successfully." -ForegroundColor Green
Write-Host "Backup: $BackupRoot" -ForegroundColor DarkGray
Write-Host "No database migration, npm build, or queue worker is required for this patch." -ForegroundColor DarkGray
