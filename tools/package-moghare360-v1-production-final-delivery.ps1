#Requires -Version 5.1
$ErrorActionPreference = "Stop"
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$RepoRoot = Split-Path -Parent $ScriptDir
. (Join-Path $ScriptDir "package-mogh-common.ps1")

$deps = @(
    "package-moghare360-v1-production-installer.ps1",
    "package-moghare360-v1-auto-deploy.ps1",
    "package-moghare360-v1-saas-deploy.ps1",
    "package-moghare360-mirror-site.ps1"
)
foreach ($d in $deps) {
    $zp = switch -Wildcard ($d) {
        "*production-installer*" { Join-Path $RepoRoot "release\moghare360-v1-production-installer.zip" }
        "*auto-deploy*" { Join-Path $RepoRoot "release\moghare360-v1-auto-deploy.zip" }
        "*saas-deploy*" { Join-Path $RepoRoot "release\moghare360-v1-saas-deploy.zip" }
        "*mirror-site*" { Join-Path $RepoRoot "release\moghare360-mirror-site-package.zip" }
    }
    if (-not (Test-Path $zp)) {
        & (Join-Path $ScriptDir $d)
    }
}

$BundleDir = Join-Path $RepoRoot "release\moghare360-v1-production-final-delivery"
if (Test-Path $BundleDir) { Remove-Item $BundleDir -Recurse -Force }
New-Item -ItemType Directory -Path $BundleDir -Force | Out-Null

$zipNames = @(
    "moghare360-v1-production-installer.zip",
    "moghare360-v1-auto-deploy.zip",
    "moghare360-v1-saas-deploy.zip",
    "moghare360-mirror-site-package.zip"
)
foreach ($z in $zipNames) {
    Copy-Item (Join-Path $RepoRoot "release\$z") (Join-Path $BundleDir $z) -Force
}

Copy-MoghTreeSafe -SourceRoot (Join-Path $RepoRoot "docs\release") -DestRoot (Join-Path $BundleDir "docs\release")

@"
# MOGHARE360 V1 Production Final Delivery

## Contents
- moghare360-v1-production-installer.zip
- moghare360-v1-auto-deploy.zip
- moghare360-v1-saas-deploy.zip
- moghare360-mirror-site-package.zip
- docs/release guides

## Security
- No credentials in ZIP
- No private config in ZIP
- No real customer data in ZIP

Date: $(Get-Date -Format 'yyyy-MM-dd HH:mm')
"@ | Set-Content (Join-Path $BundleDir "README_V1_PRODUCTION_FA.md") -Encoding UTF8

$ZipRepo = Join-Path $RepoRoot "release\moghare360-v1-production-final-delivery.zip"
$ZipWeb = Join-Path $RepoRoot "public_html\release\moghare360-v1-production-final-delivery.zip"
$ZipDownloads = Join-Path $env:USERPROFILE "Downloads\moghare360-v1-production-final-delivery.zip"

Publish-MoghZip -PackageDir $BundleDir -ZipRepo $ZipRepo -ZipWeb $ZipWeb -ZipDownloads $ZipDownloads
Write-Host "V1 PRODUCTION FINAL DELIVERY CREATED - PASS"
