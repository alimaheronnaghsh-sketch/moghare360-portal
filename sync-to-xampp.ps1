# MOGHARE360 Sync Script
# Git Source -> XAMPP Runtime

$source = "C:\Users\User\Documents\GitHub\alimaheronnaghsh-sketch\moghare360-portal"
$target = "C:\xampp\htdocs\moghare360"

Write-Host "MOGHARE360 Sync Started..." -ForegroundColor Cyan
Write-Host "Source: $source"
Write-Host "Target: $target"

if (!(Test-Path $source)) {
    Write-Host "SOURCE NOT FOUND. Sync stopped." -ForegroundColor Red
    exit 1
}

if (!(Test-Path $target)) {
    Write-Host "TARGET NOT FOUND. Creating target folder..." -ForegroundColor Yellow
    New-Item -ItemType Directory -Force -Path $target | Out-Null
}

robocopy $source $target /E /XD .git node_modules .idea .vscode /XF *.zip *.bak sync-to-xampp.ps1 sync-to-xampp.ps1.txt

$code = $LASTEXITCODE

if ($code -le 7) {
    Write-Host "MOGHARE360 Sync Completed Successfully." -ForegroundColor Green
    exit 0
}

Write-Host "MOGHARE360 Sync Failed. Robocopy exit code: $code" -ForegroundColor Red
exit $code
