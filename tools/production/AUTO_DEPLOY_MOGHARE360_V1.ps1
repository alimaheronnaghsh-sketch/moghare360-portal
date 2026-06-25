#Requires -Version 5.1
<#
.SYNOPSIS
  MOGHARE360 V1 Auto Deploy - build packages, install, verify, report.
#>
param(
    [string]$InstallPath = "C:\xampp\htdocs\moghare360",
    [switch]$CheckOnly,
    [switch]$SkipInstall,
    [switch]$CreateConfig
)

$ErrorActionPreference = "Stop"
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$RepoRoot = Split-Path (Split-Path $ScriptDir -Parent) -Parent
$ToolsDir = Join-Path $RepoRoot "tools"
$ReportPath = Join-Path $RepoRoot "DEPLOYMENT_REPORT.md"

. (Join-Path $ToolsDir "package-mogh-common.ps1")

$DeployReport = @()
function Add-DeployReport([string]$Line) {
    $script:DeployReport += $Line
    Write-Host $Line
}

Add-DeployReport "# MOGHARE360 V1 Auto Deploy Report"
Add-DeployReport "Started: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"

$packageScripts = @(
    "package-moghare360-v1-production-installer.ps1",
    "package-moghare360-v1-auto-deploy.ps1",
    "package-moghare360-v1-saas-deploy.ps1",
    "package-moghare360-mirror-site.ps1",
    "package-moghare360-v1-production-final-delivery.ps1"
)

if (-not $CheckOnly) {
    Add-DeployReport "## Build Packages"
    foreach ($ps1 in $packageScripts) {
        $path = Join-Path $ToolsDir $ps1
        if (Test-Path $path) {
            try {
                & $path
                Add-DeployReport "- Built: $ps1 PASS"
            } catch {
                Add-DeployReport "- FAIL: $ps1 - $($_.Exception.Message)"
                throw
            }
        }
    }
}

Add-DeployReport "## ZIP Inspection"
$zips = @(
    "release\moghare360-v1-production-installer.zip",
    "release\moghare360-v1-auto-deploy.zip",
    "release\moghare360-v1-saas-deploy.zip",
    "release\moghare360-mirror-site-package.zip",
    "release\moghare360-v1-production-final-delivery.zip"
)
foreach ($z in $zips) {
    $full = Join-Path $RepoRoot $z
    if (Test-Path $full) {
        Test-MoghZipArchive -ZipPath $full
        Add-DeployReport "- Forbidden check PASS: $z"
    } else {
        Add-DeployReport "- WARN missing: $z"
    }
}

if ($CheckOnly) {
    Add-DeployReport "CheckOnly complete."
    $DeployReport | Set-Content -Path $ReportPath -Encoding UTF8
    exit 0
}

if (-not $SkipInstall) {
    Add-DeployReport "## Local Install"
    $installer = Join-Path $ScriptDir "INSTALL_MOGHARE360_V1.ps1"
    & $installer -InstallPath $InstallPath -RepoRoot $RepoRoot -CreateConfig:$CreateConfig
    Add-DeployReport "- Installer executed"
}

Add-DeployReport "## Smoke Test"
$phpExe = "C:\xampp\php\php.exe"
if (-not (Test-Path $phpExe)) { $phpExe = "php" }
$smoke = Join-Path $ToolsDir "test-v1-production-run-smoke.php"
if (Test-Path $smoke) {
    & $phpExe $smoke
    if ($LASTEXITCODE -ne 0) {
        Add-DeployReport "- Smoke test FAILED"
        Add-DeployReport "Rollback: restore htdocs backup from INSTALL_REPORT.md"
        $DeployReport | Set-Content -Path $ReportPath -Encoding UTF8
        exit 1
    }
    Add-DeployReport "- Smoke test PASS"
}

Add-DeployReport "## Canonical Database Test"
$canonTest = Join-Path $ToolsDir "test-v1-canonical-database.php"
if (Test-Path $canonTest) {
    & $phpExe $canonTest
    if ($LASTEXITCODE -ne 0) {
        Add-DeployReport "- Canonical database test FAILED"
        Add-DeployReport "Rollback: restore htdocs backup from INSTALL_REPORT.md"
        $DeployReport | Set-Content -Path $ReportPath -Encoding UTF8
        exit 1
    }
    Add-DeployReport "- Canonical database test PASS"
}

Add-DeployReport ""
Add-DeployReport "Completed: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
Add-DeployReport "MOGHARE360 V1 SaaS-enabled Production Release deploy complete."

$DeployReport | Set-Content -Path $ReportPath -Encoding UTF8
Write-Host "AUTO DEPLOY COMPLETE" -ForegroundColor Green
