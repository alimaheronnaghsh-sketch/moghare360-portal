#Requires -Version 5.1
$ErrorActionPreference = "Stop"

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$RepoRoot = Split-Path -Parent $ScriptDir
. (Join-Path $ScriptDir "package-mogh-common.ps1")

$PackageDir = Join-Path $RepoRoot "release\moghare360-desktop-run-package"
$ZipRepo = Join-Path $RepoRoot "release\moghare360-desktop-run-package.zip"
$ZipWeb = Join-Path $RepoRoot "public_html\release\moghare360-desktop-run-package.zip"
$ZipDownloads = Join-Path $env:USERPROFILE "Downloads\moghare360-desktop-run-package.zip"

if (Test-Path $PackageDir) { Remove-Item $PackageDir -Recurse -Force }

New-Item -ItemType Directory -Path $PackageDir -Force | Out-Null

Copy-MoghTreeSafe -SourceRoot (Join-Path $RepoRoot "public_html") -DestRoot (Join-Path $PackageDir "public_html") -ExcludeReleaseSubdir
Copy-MoghTreeSafe -SourceRoot (Join-Path $RepoRoot "docs") -DestRoot (Join-Path $PackageDir "docs") -ExcludeReleaseSubdir

$sqlSrc = Join-Path $RepoRoot "public_html\sql"
if (Test-Path $sqlSrc) {
    Copy-MoghTreeSafe -SourceRoot $sqlSrc -DestRoot (Join-Path $PackageDir "sql")
}

$toolsDest = Join-Path $PackageDir "tools"
New-Item -ItemType Directory -Path $toolsDest -Force | Out-Null
Get-ChildItem (Join-Path $RepoRoot "tools") -Filter "test-phase-*.php" -File -ErrorAction SilentlyContinue | ForEach-Object {
    Copy-Item $_.FullName (Join-Path $toolsDest $_.Name) -Force
}
Get-ChildItem (Join-Path $RepoRoot "tools") -Filter "test-wave-*.php" -File -ErrorAction SilentlyContinue | ForEach-Object {
    Copy-Item $_.FullName (Join-Path $toolsDest $_.Name) -Force
}

$templates = Join-Path $RepoRoot "tools\desktop-run-templates"
Get-ChildItem $templates -File | ForEach-Object {
    Copy-Item $_.FullName (Join-Path $PackageDir $_.Name) -Force
}

$luxuryCss = Join-Path $RepoRoot "release\moghare360-mirror-site-package\public_html\assets\css\moghare360-v1-luxury-ui.css"
if (Test-Path $luxuryCss) {
    $destCssDir = Join-Path $PackageDir "public_html\assets\css"
    New-Item -ItemType Directory -Path $destCssDir -Force | Out-Null
    Copy-Item $luxuryCss (Join-Path $destCssDir "moghare360-v1-luxury-ui.css") -Force
}

@"
# MOGHARE360 Desktop Run Package - Release Report

## Included
- public_html (sanitized, no config.php / private)
- docs
- sql scripts (no backups)
- tools test scripts
- Windows launchers

## Excluded
- private/, uploads/, logs/, backups/, .git/
- config.php, credentials, real customer data

## ZIP Paths
- release/moghare360-desktop-run-package.zip
- public_html/release/moghare360-desktop-run-package.zip
- Downloads/moghare360-desktop-run-package.zip

## Status
PACKAGED - $(Get-Date -Format 'yyyy-MM-dd HH:mm')
"@ | Set-Content -Path (Join-Path $PackageDir "RELEASE_RUN_REPORT.md") -Encoding UTF8

Publish-MoghZip -PackageDir $PackageDir -ZipRepo $ZipRepo -ZipWeb $ZipWeb -ZipDownloads $ZipDownloads

Write-Host "DESKTOP RUN PACKAGE CREATED - PASS"
Write-Host "Path: release/moghare360-desktop-run-package.zip"
