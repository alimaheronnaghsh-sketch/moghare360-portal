#Requires -Version 5.1
$ErrorActionPreference = "Stop"
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$RepoRoot = Split-Path -Parent $ScriptDir
. (Join-Path $ScriptDir "package-mogh-common.ps1")

$PackageDir = Join-Path $RepoRoot "release\moghare360-v1-production-installer"
$ZipRepo = Join-Path $RepoRoot "release\moghare360-v1-production-installer.zip"
$ZipWeb = Join-Path $RepoRoot "public_html\release\moghare360-v1-production-installer.zip"
$ZipDownloads = Join-Path $env:USERPROFILE "Downloads\moghare360-v1-production-installer.zip"

if (Test-Path $PackageDir) { Remove-Item $PackageDir -Recurse -Force }
New-Item -ItemType Directory -Path $PackageDir -Force | Out-Null

Copy-MoghTreeSafe -SourceRoot (Join-Path $RepoRoot "public_html") -DestRoot (Join-Path $PackageDir "public_html") -ExcludeReleaseSubdir
Copy-MoghTreeSafe -SourceRoot (Join-Path $RepoRoot "includes") -DestRoot (Join-Path $PackageDir "includes")
Copy-MoghTreeSafe -SourceRoot (Join-Path $RepoRoot "docs\release") -DestRoot (Join-Path $PackageDir "docs\release")

$sqlSrc = Join-Path $RepoRoot "public_html\sql\sqlserver"
if (Test-Path $sqlSrc) {
    Copy-MoghTreeSafe -SourceRoot $sqlSrc -DestRoot (Join-Path $PackageDir "sql\sqlserver")
}

$prodTools = Join-Path $RepoRoot "tools\production"
$toolsDest = Join-Path $PackageDir "tools"
New-Item -ItemType Directory -Path $toolsDest -Force | Out-Null
Copy-Item (Join-Path $prodTools "*") $toolsDest -Force
$templates = Join-Path $RepoRoot "tools\desktop-run-templates"
Copy-Item (Join-Path $templates "*") $toolsDest -Force

@"
# MOGHARE360 V1 Production Installer Package
SaaS-enabled Production Release
No credentials / no private config / no real customer data in ZIP
"@ | Set-Content (Join-Path $PackageDir "README_INSTALLER.md") -Encoding UTF8

Publish-MoghZip -PackageDir $PackageDir -ZipRepo $ZipRepo -ZipWeb $ZipWeb -ZipDownloads $ZipDownloads
Write-Host "V1 PRODUCTION INSTALLER PACKAGE CREATED - PASS"
