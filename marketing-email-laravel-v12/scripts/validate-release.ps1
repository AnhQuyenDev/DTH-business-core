$ErrorActionPreference = "Stop"

Write-Host "DTH Business Core - Release Validation" -ForegroundColor Cyan

$requiredExtensions = @("mbstring", "dom", "xml", "xmlwriter", "pdo")
$modules = php -m
foreach ($extension in $requiredExtensions) {
    if (-not ($modules -match "(?im)^$([regex]::Escape($extension))$")) {
        throw "Missing PHP extension: $extension"
    }
}

if (-not (($modules -match "(?im)^pdo_sqlite$") -or ($modules -match "(?im)^sqlite3$"))) {
    throw "Tests use SQLite in-memory. Enable pdo_sqlite/sqlite3 before running the suite."
}

Write-Host "[1/6] Clear development caches"
php artisan optimize:clear
if ($LASTEXITCODE -ne 0) { throw "optimize:clear failed" }

Write-Host "[2/6] PHP syntax scan"
$phpFiles = Get-ChildItem app,bootstrap,config,database,routes,tests -Recurse -Filter *.php
foreach ($file in $phpFiles) {
    php -l $file.FullName | Out-Null
    if ($LASTEXITCODE -ne 0) { throw "PHP syntax failed: $($file.FullName)" }
}

Write-Host "[3/6] Laravel automated test suite"
php artisan test
if ($LASTEXITCODE -ne 0) { throw "Laravel test suite failed" }

Write-Host "[4/6] Frontend production build"
npm run build
if ($LASTEXITCODE -ne 0) { throw "Frontend build failed" }

Write-Host "[5/6] Route discovery"
php artisan route:list --except-vendor | Out-Null
if ($LASTEXITCODE -ne 0) { throw "Route discovery failed" }

Write-Host "[6/6] Clear generated validation caches"
php artisan optimize:clear
if ($LASTEXITCODE -ne 0) { throw "Final optimize:clear failed" }

Write-Host "VALIDATION PASSED. Continue with staging UAT before production." -ForegroundColor Green
