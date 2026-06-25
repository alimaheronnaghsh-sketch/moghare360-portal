#Requires -Version 5.1
$ErrorActionPreference = "Continue"

Write-Host "=== MOGHARE360 Requirements Check ===" -ForegroundColor Cyan

$target = "C:\xampp\htdocs\moghare360"
$localUrl = "http://localhost:8080/moghare360/"

function Test-ItemOk($label, $ok) {
    if ($ok) { Write-Host "[OK] $label" -ForegroundColor Green }
    else { Write-Host "[WARN] $label" -ForegroundColor Yellow }
}

Test-ItemOk "XAMPP path" (Test-Path "C:\xampp")
Test-ItemOk "PHP" (Get-Command php -ErrorAction SilentlyContinue)
Test-ItemOk "Local copy path" (Test-Path $target)
Test-ItemOk "SQL Service" ((Get-Service -Name "MSSQL*" -ErrorAction SilentlyContinue | Where-Object { $_.Status -eq 'Running' }).Count -gt 0)

if (Get-Command php -ErrorAction SilentlyContinue) {
    $ver = & php -r "echo PHP_VERSION;"
    Test-ItemOk "PHP version: $ver" ($ver -match '^\d')
}

if (Test-Path $target) {
    $sample = Join-Path $target "public_html\index.php"
    if (-not (Test-Path $sample)) { $sample = Join-Path $target "index.php" }
    if (Test-Path $sample) {
        if (Get-Command php -ErrorAction SilentlyContinue) {
            & php -l $sample 2>&1 | Out-Null
            Test-ItemOk "PHP syntax sample" ($LASTEXITCODE -eq 0)
        }
    }
}

Write-Host "Local URL: $localUrl"
