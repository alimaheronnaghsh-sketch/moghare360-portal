#Requires -Version 5.1
<#
.SYNOPSIS
  Build clean cPanel public site ZIP (flat root, no secrets, no debug endpoints).
#>
$ErrorActionPreference = "Stop"

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$RepoRoot = Split-Path -Parent $ScriptDir
. (Join-Path $ScriptDir "package-mogh-common.ps1")

$SourceDir = Join-Path $RepoRoot "public_html"
$StageDir = Join-Path $RepoRoot "release\_cpanel_public_final_clean_stage"
$ZipOut = Join-Path $RepoRoot "release\moghare360-cpanel-public-final-clean.zip"

if (-not (Test-Path $SourceDir)) {
    throw "public_html not found: $SourceDir"
}

if (Test-Path $StageDir) {
    Remove-Item -LiteralPath $StageDir -Recurse -Force
}
New-Item -ItemType Directory -Path $StageDir -Force | Out-Null

$relativeFiles = @(
    "customer-request.php",
    "staff-login.php",
    "owner-login.php",
    "user-access-request.php",
    "company-owner-dashboard.php",
    "mirror-health.php",
    "mirror-config.example.php",
    "manifest.webmanifest",
    "service-worker.js",
    "includes\mirror-layout.php",
    "includes\mirror-api-client.php",
    "assets\css\mirror.css",
    "assets\css\moghare360-v1-luxury-ui.css",
    "assets\js\iran-provinces-cities.js",
    "assets\js\vehicle-brand-classes.js",
    "assets\js\customer-form.js",
    "assets\js\m360-jalali-datepicker.js",
    "api\customer\request.php",
    "api\mirror\health.php"
)

foreach ($rel in $relativeFiles) {
    $from = Join-Path $SourceDir $rel
    $to = Join-Path $StageDir $rel
    if (-not (Test-Path $from)) {
        throw "Required source missing: $rel"
    }
    $dir = Split-Path -Parent $to
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
    }
    Copy-Item -LiteralPath $from -Destination $to -Force
    Write-Host "- STAGE $rel"
}

$indexSrc = Join-Path $SourceDir "cpanel-public-index.php"
if (-not (Test-Path $indexSrc)) {
    throw "cpanel-public-index.php missing"
}
Copy-Item -LiteralPath $indexSrc -Destination (Join-Path $StageDir "index.php") -Force
Write-Host "- STAGE index.php (from cpanel-public-index.php)"

foreach ($assetDir in @("assets\brand", "assets\icons")) {
    $fromDir = Join-Path $SourceDir $assetDir
    if (Test-Path $fromDir) {
        $toDir = Join-Path $StageDir $assetDir
        if (-not (Test-Path $toDir)) {
            New-Item -ItemType Directory -Path $toDir -Force | Out-Null
        }
        Copy-Item -Path (Join-Path $fromDir "*") -Destination $toDir -Force -ErrorAction SilentlyContinue
        Write-Host "- STAGE $assetDir/*"
    }
}

$forbidden = @('config.php', 'erp-config.php', 'mirror-config.php', 'cpanel-public-index.php', 'debug-pending.php')
Get-ChildItem -Path $StageDir -Recurse -File -Force | ForEach-Object {
    if ($forbidden -contains $_.Name) {
        Remove-Item -LiteralPath $_.FullName -Force
        Write-Host "- REMOVE forbidden: $($_.Name)"
    }
}

if (Test-Path $ZipOut) {
    Remove-Item -LiteralPath $ZipOut -Force
}

Add-Type -AssemblyName System.IO.Compression.FileSystem
[System.IO.Compression.ZipFile]::CreateFromDirectory($StageDir, $ZipOut)

Test-MoghZipArchive -ZipPath $ZipOut

Add-Type -AssemblyName System.IO.Compression.FileSystem
$zip = [System.IO.Compression.ZipFile]::OpenRead($ZipOut)
try {
    foreach ($entry in $zip.Entries) {
        $name = $entry.FullName.Replace('\', '/')
        if ($name -match '(^|/)public_html/public_html') {
            throw "NESTED public_html/public_html: $name"
        }
        if ($name -match '\.zip$') {
            throw "ZIP inside ZIP: $name"
        }
        if ($name -match 'debug-pending\.php$') {
            throw "DEBUG endpoint in package: $name"
        }
        if ($name -match '(^|/)api/sync/') {
            throw "Sync API in public package: $name"
        }
    }
} finally {
    $zip.Dispose()
}

Write-Host ""
Write-Host "CPANEL PUBLIC FINAL CLEAN ZIP CREATED - PASS" -ForegroundColor Green
Write-Host "Path: release/moghare360-cpanel-public-final-clean.zip"
