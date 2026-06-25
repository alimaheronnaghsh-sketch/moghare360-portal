#Requires -Version 5.1
$ErrorActionPreference = "Stop"
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$RepoRoot = Split-Path -Parent $ScriptDir
. (Join-Path $ScriptDir "package-mogh-common.ps1")

$PackageDir = Join-Path $RepoRoot "release\moghare360-v1-saas-deploy"
$ZipRepo = Join-Path $RepoRoot "release\moghare360-v1-saas-deploy.zip"
$ZipWeb = Join-Path $RepoRoot "public_html\release\moghare360-v1-saas-deploy.zip"
$ZipDownloads = Join-Path $env:USERPROFILE "Downloads\moghare360-v1-saas-deploy.zip"

if (Test-Path $PackageDir) { Remove-Item $PackageDir -Recurse -Force }
New-Item -ItemType Directory -Path $PackageDir -Force | Out-Null

$saasIncludes = @(
    'moghare360-saas-config-loader.php',
    'moghare360-saas-tenant-context.php',
    'moghare360-saas-storage-adapter.php',
    'moghare360-v1-api-bootstrap.php'
)
$incDest = Join-Path $PackageDir "public_html\includes"
New-Item -ItemType Directory -Path $incDest -Force | Out-Null
foreach ($f in $saasIncludes) {
    Copy-Item (Join-Path $RepoRoot "public_html\includes\$f") $incDest -Force
}

$apiSrc = Join-Path $RepoRoot "public_html\api"
Copy-MoghTreeSafe -SourceRoot $apiSrc -DestRoot (Join-Path $PackageDir "public_html\api")
Copy-Item (Join-Path $RepoRoot "public_html\saas-health.php") (Join-Path $PackageDir "public_html\saas-health.php") -Force
Copy-Item (Join-Path $RepoRoot "public_html\sql\sqlserver\v1_saas_activation_foundation.sql") (Join-Path $PackageDir "sql\v1_saas_activation_foundation.sql") -Force
Copy-MoghTreeSafe -SourceRoot (Join-Path $RepoRoot "docs\release") -DestRoot (Join-Path $PackageDir "docs\release")

Publish-MoghZip -PackageDir $PackageDir -ZipRepo $ZipRepo -ZipWeb $ZipWeb -ZipDownloads $ZipDownloads
Write-Host "V1 SAAS DEPLOY PACKAGE CREATED - PASS"
