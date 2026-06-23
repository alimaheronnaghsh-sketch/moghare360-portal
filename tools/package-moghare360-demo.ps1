#Requires -Version 5.1
$ErrorActionPreference = "Stop"

# MOGHARE360 — Demo Package (commercial/brand/product only)
# Exclusions: private, config.php, config.example.php, private/erp-config.php,
# private/erp-config.example.php, .git, logs, uploads, backups, release, *.bak, *.log
# No DB, no secrets, no real customer data — Compress-Archive

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$RepoRoot = Split-Path -Parent $ScriptDir

if (-not (Test-Path (Join-Path $RepoRoot "public_html"))) {
    Write-Error "Run from repo root context. public_html not found at: $RepoRoot"
    exit 1
}

$ReleaseRoot = Join-Path $RepoRoot "release"
$PackageDir = Join-Path $ReleaseRoot "moghare360-demo-package"
$ZipRepo = Join-Path $ReleaseRoot "moghare360-demo-package.zip"
$ZipWebDir = Join-Path $RepoRoot "public_html\release"
$ZipWeb = Join-Path $ZipWebDir "moghare360-demo-package.zip"

$demoPages = @(
    "moghare360-commercial-demo.php",
    "moghare360-sales-showcase.php",
    "moghare360-product-packages.php",
    "moghare360-license-preview.php",
    "moghare360-commercial-checklist.php",
    "moghare360-final-release-report.php",
    "moghare360-demo-package.php",
    "erp-brand-system.php",
    "erp-localization-audit.php",
    "erp-asset-registry.php",
    "erp-release-package-dashboard.php",
    "moghare360-release-download.php"
)

$cssFiles = @(
    "moghare360-design-tokens.css",
    "moghare360-rtl.css",
    "moghare360-customer-core.css",
    "moghare360-brand-localization.css",
    "moghare360-commercial-system.css",
    "moghare360-release-package.css"
)

if (Test-Path $PackageDir) { Remove-Item $PackageDir -Recurse -Force }
New-Item -ItemType Directory -Path $ReleaseRoot -Force | Out-Null
New-Item -ItemType Directory -Path $PackageDir -Force | Out-Null

$pubSrc = Join-Path $RepoRoot "public_html"
$pubDest = Join-Path $PackageDir "public_html"
New-Item -ItemType Directory -Path $pubDest -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $pubDest "assets\moghare360-ui") -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $pubDest "assets\moghare360-brand") -Force | Out-Null

foreach ($page in $demoPages) {
    $src = Join-Path $pubSrc $page
    if (Test-Path $src) {
        Copy-Item $src (Join-Path $pubDest $page) -Force
    }
}

$uiSrc = Join-Path $pubSrc "assets\moghare360-ui"
$uiDest = Join-Path $pubDest "assets\moghare360-ui"
foreach ($css in $cssFiles) {
    $src = Join-Path $uiSrc $css
    if (Test-Path $src) { Copy-Item $src (Join-Path $uiDest $css) -Force }
}

$brandSrc = Join-Path $pubSrc "assets\moghare360-brand"
$brandDest = Join-Path $pubDest "assets\moghare360-brand"
if (Test-Path $brandSrc) {
    & robocopy $brandSrc $brandDest /E /XF "*.zip" "*.log" "*.bak" | Out-Null
}

foreach ($docDir in @("product", "release", "deployment")) {
    $src = Join-Path $RepoRoot "docs\$docDir"
    if (Test-Path $src) {
        $dest = Join-Path $PackageDir "docs\$docDir"
        & robocopy $src $dest /E /XD ".git" "private" "logs" "backups" "uploads" "release" | Out-Null
    }
}

Copy-Item (Join-Path $RepoRoot "docs\release\MOGHARE360_DEMO_PACKAGE_MANIFEST.md") (Join-Path $PackageDir "DEMO_MANIFEST.md") -Force -ErrorAction SilentlyContinue

if (Test-Path $ZipRepo) { Remove-Item $ZipRepo -Force }
if (Test-Path $ZipWeb) { Remove-Item $ZipWeb -Force }
Compress-Archive -Path (Join-Path $PackageDir "*") -DestinationPath $ZipRepo -Force

$zipEntries = @(& tar -tf $ZipRepo 2>&1)
if ($LASTEXITCODE -ne 0) {
    Write-Error "tar -tf failed for: $ZipRepo"
    exit 1
}
foreach ($entry in $zipEntries) {
    $norm = ($entry -replace '\\', '/').ToLowerInvariant()
    if ($norm -match '(^|/)(private|logs|uploads|backups|\.git)(/|$)') {
        Write-Host "FORBIDDEN CONTENT FOUND"
        Write-Host "Entry: $entry"
        exit 1
    }
    if ($norm -match '^release(/|$)' -or $norm -match '^public_html/release(/|$)') {
        Write-Host "FORBIDDEN CONTENT FOUND"
        Write-Host "Entry: $entry"
        exit 1
    }
    $leaf = [System.IO.Path]::GetFileName($norm.Replace('/', [System.IO.Path]::DirectorySeparatorChar))
    if ($leaf -in @('config.php', 'config.example.php', 'erp-config.php', 'erp-config.example.php')) {
        Write-Host "FORBIDDEN CONTENT FOUND"
        Write-Host "Entry: $entry"
        exit 1
    }
    if ($leaf -match '\.bak' -or $leaf -match '\.(log|tmp)$') {
        Write-Host "FORBIDDEN CONTENT FOUND"
        Write-Host "Entry: $entry"
        exit 1
    }
}

New-Item -ItemType Directory -Path $ZipWebDir -Force | Out-Null
Copy-Item $ZipRepo $ZipWeb -Force

Write-Host "DEMO PACKAGE CREATED"
Write-Host "Path: release/moghare360-demo-package.zip"
Write-Host "Web copy: public_html/release/moghare360-demo-package.zip"
Write-Host "Exclusions verified"
