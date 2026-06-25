#Requires -Version 5.1
$ErrorActionPreference = "Stop"

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$RepoRoot = Split-Path -Parent $ScriptDir
. (Join-Path $ScriptDir "package-mogh-common.ps1")

$desktopZip = Join-Path $RepoRoot "release\moghare360-desktop-run-package.zip"
$mirrorZip = Join-Path $RepoRoot "release\moghare360-mirror-site-package.zip"

if (-not (Test-Path $desktopZip)) {
    & (Join-Path $ScriptDir "package-moghare360-desktop-run.ps1")
}
if (-not (Test-Path $mirrorZip)) {
    & (Join-Path $ScriptDir "package-moghare360-mirror-site.ps1")
}

$BundleDir = Join-Path $RepoRoot "release\moghare360-v1-final-delivery"
if (Test-Path $BundleDir) { Remove-Item $BundleDir -Recurse -Force }
New-Item -ItemType Directory -Path $BundleDir -Force | Out-Null

Copy-Item $desktopZip (Join-Path $BundleDir "moghare360-desktop-run-package.zip") -Force
Copy-Item $mirrorZip (Join-Path $BundleDir "moghare360-mirror-site-package.zip") -Force

$docsRelease = Join-Path $RepoRoot "docs\release"
if (Test-Path $docsRelease) {
    Copy-MoghTreeSafe -SourceRoot $docsRelease -DestRoot (Join-Path $BundleDir "docs\release")
}

@"
# MOGHARE360 V1 Final Delivery - README فارسی

## محتویات
1. moghare360-desktop-run-package.zip - اجرای محلی روی ویندوز
2. moghare360-mirror-site-package.zip - آپلود روی moghareh360.ir (Mirror Only)
3. docs/release — راهنماها و Manifest

## قوانین
- No Cloud Data
- No Host Database
- Master Server = Laptop

## PWA
Mirror Package شامل manifest و service-worker برای نصب روی موبایل/تبلت/دسکتاپ است.

تاریخ: $(Get-Date -Format 'yyyy-MM-dd HH:mm')
"@ | Set-Content (Join-Path $BundleDir "README_V1_FINAL_FA.md") -Encoding UTF8

$ZipRepo = Join-Path $RepoRoot "release\moghare360-v1-final-delivery.zip"
$ZipWeb = Join-Path $RepoRoot "public_html\release\moghare360-v1-final-delivery.zip"
$ZipDownloads = Join-Path $env:USERPROFILE "Downloads\moghare360-v1-final-delivery.zip"

Publish-MoghZip -PackageDir $BundleDir -ZipRepo $ZipRepo -ZipWeb $ZipWeb -ZipDownloads $ZipDownloads

Write-Host "V1 FINAL DELIVERY BUNDLE CREATED - PASS"
