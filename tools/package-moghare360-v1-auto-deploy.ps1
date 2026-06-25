#Requires -Version 5.1
$ErrorActionPreference = "Stop"
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$RepoRoot = Split-Path -Parent $ScriptDir
. (Join-Path $ScriptDir "package-mogh-common.ps1")

$PackageDir = Join-Path $RepoRoot "release\moghare360-v1-auto-deploy"
$ZipRepo = Join-Path $RepoRoot "release\moghare360-v1-auto-deploy.zip"
$ZipWeb = Join-Path $RepoRoot "public_html\release\moghare360-v1-auto-deploy.zip"
$ZipDownloads = Join-Path $env:USERPROFILE "Downloads\moghare360-v1-auto-deploy.zip"

if (Test-Path $PackageDir) { Remove-Item $PackageDir -Recurse -Force }
New-Item -ItemType Directory -Path $PackageDir -Force | Out-Null

Copy-MoghTreeSafe -SourceRoot (Join-Path $RepoRoot "tools") -DestRoot (Join-Path $PackageDir "tools") -ExcludeReleaseSubdir
Copy-MoghTreeSafe -SourceRoot (Join-Path $RepoRoot "public_html") -DestRoot (Join-Path $PackageDir "public_html") -ExcludeReleaseSubdir
Copy-MoghTreeSafe -SourceRoot (Join-Path $RepoRoot "includes") -DestRoot (Join-Path $PackageDir "includes")
Copy-MoghTreeSafe -SourceRoot (Join-Path $RepoRoot "docs\release") -DestRoot (Join-Path $PackageDir "docs\release")

Publish-MoghZip -PackageDir $PackageDir -ZipRepo $ZipRepo -ZipWeb $ZipWeb -ZipDownloads $ZipDownloads
Write-Host "V1 AUTO DEPLOY PACKAGE CREATED - PASS"
