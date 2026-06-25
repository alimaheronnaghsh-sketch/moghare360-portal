#Requires -Version 5.1
$ErrorActionPreference = "Stop"

$Source = $PSScriptRoot
if (Test-Path (Join-Path $PSScriptRoot "public_html")) {
    $Source = $PSScriptRoot
}

$Target = "C:\xampp\htdocs\moghare360"
$Backup = "C:\xampp\htdocs\moghare360_backup_" + (Get-Date -Format "yyyyMMdd_HHmmss")

Write-Host "MOGHARE360 — Safe local install copy" -ForegroundColor Cyan
Write-Host "Source: $Source"
Write-Host "Target: $Target"

if (Test-Path $Target) {
    Write-Host "Creating backup: $Backup"
    Copy-Item -LiteralPath $Target -Destination $Backup -Recurse -Force
}

if (-not (Test-Path "C:\xampp\htdocs")) {
    throw "C:\xampp\htdocs not found. Install XAMPP first."
}

$robocopy = @(
    "robocopy", "`"$Source`"", "`"$Target`"", "/E",
    "/XD", "private", ".git", "logs", "backups", "uploads", "node_modules", "vendor",
    "/XF", "config.php", "erp-config.php", "mirror-config.php",
    "/NFL", "/NDL", "/NJH", "/NJS", "/nc", "/ns", "/np"
)
cmd /c ($robocopy -join " ")
Write-Host "Copy complete. Configure private/erp-config.php manually — not included in package." -ForegroundColor Green
