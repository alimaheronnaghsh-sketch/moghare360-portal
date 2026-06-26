#Requires -Version 5.1
<#
.SYNOPSIS
  Sync public site UX files from repo public_html to local XAMPP runtime.
#>
param(
    [string]$RepoRoot = "",
    [string]$InstallPath = "C:\xampp\htdocs\moghare360",
    [switch]$SkipMirrorConfig
)

$ErrorActionPreference = "Stop"
if ($RepoRoot -eq "") {
    $RepoRoot = Split-Path (Split-Path $PSScriptRoot -Parent) -Parent
}

$srcRoot = Join-Path $RepoRoot "public_html"
if (-not (Test-Path $srcRoot)) {
    throw "public_html not found at $srcRoot"
}

if (-not (Test-Path $InstallPath)) {
    New-Item -ItemType Directory -Path $InstallPath -Force | Out-Null
}

$report = @()
function Add-Line([string]$Line) {
    $script:report += $Line
    Write-Host $Line
}

Add-Line "# MOGHARE360 Public Site Sync Report"
Add-Line "Source: $srcRoot"
Add-Line "Target: $InstallPath"
Add-Line ""

$ensureDirs = @(
    "includes",
    "api\customer",
    "assets\js",
    "assets\css"
)
foreach ($dirRel in $ensureDirs) {
    $dirPath = Join-Path $InstallPath $dirRel
    if (-not (Test-Path $dirPath)) {
        New-Item -ItemType Directory -Path $dirPath -Force | Out-Null
        Add-Line "- MKDIR $dirRel"
    }
}

$relativeFiles = @(
    "customer-request.php",
    "staff-login.php",
    "owner-login.php",
    "company-owner-dashboard.php",
    "user-access-request.php",
    "mirror-health.php",
    "mirror-config.example.php",
    "manifest.webmanifest",
    "service-worker.js",
    "includes\mirror-layout.php",
    "includes\mirror-api-client.php",
    "includes\m360-otp-helper.php",
    "api\customer\request.php",
    "api\customer\send-otp.php",
    "api\customer\verify-otp.php",
    "assets\css\mirror.css",
    "assets\css\moghare360-v1-luxury-ui.css",
    "assets\js\iran-provinces-cities.js",
    "assets\js\vehicle-brand-classes.js",
    "assets\js\customer-form.js",
    "assets\js\m360-jalali-datepicker.js"
)

$forbidden = @('config.php', 'erp-config.php', 'mirror-config.php')

foreach ($rel in $relativeFiles) {
    if ($forbidden -contains (Split-Path $rel -Leaf)) {
        Add-Line "- SKIP forbidden: $rel"
        continue
    }
    $from = Join-Path $srcRoot $rel
    $to = Join-Path $InstallPath $rel
    if (-not (Test-Path $from)) {
        Add-Line "- WARN missing source: $rel"
        continue
    }
    $dir = Split-Path $to -Parent
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
    }
    Copy-Item -LiteralPath $from -Destination $to -Force
    Add-Line "- SYNC $rel"
}

$brandSrc = Join-Path $srcRoot "assets\brand"
$brandDst = Join-Path $InstallPath "assets\brand"
if (Test-Path $brandSrc) {
    if (-not (Test-Path $brandDst)) { New-Item -ItemType Directory -Path $brandDst -Force | Out-Null }
    Copy-Item -Path (Join-Path $brandSrc "*") -Destination $brandDst -Force -ErrorAction SilentlyContinue
    Add-Line "- SYNC assets/brand/*"
}

if (-not $SkipMirrorConfig) {
    $cfgScript = Join-Path $RepoRoot "tools\local\CREATE_LOCAL_MIRROR_CONFIG.ps1"
    if (Test-Path $cfgScript) {
        & $cfgScript -InstallPath $InstallPath -Force
        Add-Line "- mirror-config.php ensured (local runtime only)"
    }
}

Add-Line ""
Add-Line "## OTP runtime validation"
$otpCritical = @(
    "includes\m360-otp-helper.php",
    "api\customer\send-otp.php",
    "api\customer\verify-otp.php"
)
$otpMissing = @()
foreach ($rel in $otpCritical) {
    $target = Join-Path $InstallPath $rel
    if (Test-Path $target) {
        Add-Line "- OK $rel"
    } else {
        Add-Line "- FAIL missing: $rel"
        $otpMissing += $rel
    }
}
if ($otpMissing.Count -gt 0) {
    throw "OTP sync validation failed. Missing: $($otpMissing -join ', ')"
}

Add-Line ""
Add-Line "Sync complete."
$reportPath = Join-Path $RepoRoot "runtime\PUBLIC_SITE_SYNC_REPORT.md"
$runtimeDir = Split-Path $reportPath -Parent
if (-not (Test-Path $runtimeDir)) { New-Item -ItemType Directory -Path $runtimeDir -Force | Out-Null }
$report | Set-Content -Path $reportPath -Encoding UTF8
Write-Host "Report: $reportPath" -ForegroundColor Green
