param(
    [string]$Output = "DTH-BUSINESS-CORE-V1-RELEASE.zip"
)

$ErrorActionPreference = "Stop"
$root = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
$stage = Join-Path ([System.IO.Path]::GetTempPath()) ("dth-release-" + [guid]::NewGuid().ToString("N"))
New-Item -ItemType Directory -Path $stage | Out-Null

# Source deployment package. Dependencies should be installed/built on the
# target OS or in CI so Windows node_modules are never shipped to Linux.
$excludeDirs = @(".git", ".idea", ".vscode", "node_modules", "vendor", "storage\\logs", "storage\\framework", "storage\\backups")
$excludeFiles = @(".env", ".env.production", ".phpunit.result.cache", "marketing_email", "database\\database.sqlite")

Get-ChildItem -Path $root -Force | ForEach-Object {
    if ($excludeDirs -contains $_.Name -or $excludeFiles -contains $_.Name) { return }
    Copy-Item $_.FullName -Destination $stage -Recurse -Force
}

foreach ($path in $excludeDirs) {
    $target = Join-Path $stage $path
    if (Test-Path $target) { Remove-Item $target -Recurse -Force }
}
foreach ($path in $excludeFiles) {
    $target = Join-Path $stage $path
    if (Test-Path $target) { Remove-Item $target -Force }
}

# Never ship generated logs or local secrets even if nested.
Get-ChildItem $stage -Recurse -Force -File | Where-Object {
    $_.Name -eq ".env" -or $_.Extension -eq ".log" -or $_.Name -eq ".phpunit.result.cache"
} | Remove-Item -Force

if (Test-Path $Output) { Remove-Item $Output -Force }
Compress-Archive -Path (Join-Path $stage "*") -DestinationPath $Output -CompressionLevel Optimal
Remove-Item $stage -Recurse -Force
Write-Host "Created: $Output" -ForegroundColor Green
Write-Host "On target/CI run: composer install --no-dev --optimize-autoloader; npm ci; npm run build; php artisan migrate --force; php artisan system:production-warmup"
