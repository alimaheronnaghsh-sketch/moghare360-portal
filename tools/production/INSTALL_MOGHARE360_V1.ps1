#Requires -Version 5.1
<#
.SYNOPSIS
  MOGHARE360 V1 Production Installer
#>
param(
    [string]$InstallPath = "C:\xampp\htdocs\moghare360",
    [string]$RepoRoot = "",
    [string]$SqlServer = ".\SQLEXPRESS",
    [string]$DatabaseName = "moghare360_ERP",
    [string]$BaseUrl = "http://localhost:8080/moghare360/",
    [switch]$CheckOnly,
    [switch]$SkipSql,
    [switch]$CreateConfig
)

$ErrorActionPreference = "Stop"
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
if ($RepoRoot -eq "") {
    $RepoRoot = Split-Path (Split-Path $ScriptDir -Parent) -Parent
}

$ReportPath = Join-Path $RepoRoot "INSTALL_REPORT.md"
$phpExe = "C:\xampp\php\php.exe"
if (-not (Test-Path $phpExe)) { $phpExe = "php" }

function Write-ReportLine([string]$Line) {
    $script:Report += $Line
    Write-Host $Line
}

$Report = @()
Write-ReportLine "# MOGHARE360 V1 Install Report"
Write-ReportLine ""
Write-ReportLine "Started: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"

# 1. Prerequisites
$checks = @()
$checks += @{ Name = "XAMPP"; Ok = (Test-Path "C:\xampp") }
$checks += @{ Name = "Apache htdocs"; Ok = (Test-Path "C:\xampp\htdocs") }
$checks += @{ Name = "PHP"; Ok = (Test-Path $phpExe) -or (Get-Command php -ErrorAction SilentlyContinue) }
$checks += @{ Name = "SQL Service"; Ok = ((Get-Service -Name "MSSQL*" -ErrorAction SilentlyContinue | Where-Object { $_.Status -eq 'Running' }).Count -gt 0) }
$checks += @{ Name = "Repo public_html"; Ok = (Test-Path (Join-Path $RepoRoot "public_html")) }

Write-ReportLine "## Prerequisites"
foreach ($c in $checks) {
    $status = if ($c.Ok) { "PASS" } else { "WARN" }
    Write-ReportLine "- $($c.Name): $status"
}

if ($CheckOnly) {
    Write-ReportLine ""
    Write-ReportLine "CheckOnly complete."
    $Report | Set-Content -Path $ReportPath -Encoding UTF8
    exit 0
}

# 2. Backup
$backupDir = "C:\xampp\htdocs\moghare360_backup_" + (Get-Date -Format "yyyyMMdd_HHmmss")
if (Test-Path $InstallPath) {
    Write-ReportLine "## Backup"
    Copy-Item -LiteralPath $InstallPath -Destination $backupDir -Recurse -Force
    Write-ReportLine "- Backed up to $backupDir"
}

# 3. Install files
Write-ReportLine "## Install Files"
$srcPublic = Join-Path $RepoRoot "public_html"
if (-not (Test-Path $srcPublic)) { throw "public_html not found" }

if (-not (Test-Path $InstallPath)) {
    New-Item -ItemType Directory -Path $InstallPath -Force | Out-Null
}

$robocopyArgs = @(
    "robocopy", "`"$srcPublic`"", "`"$InstallPath`"", "/E",
    "/XD", "private", ".git", "logs", "backups", "uploads", "node_modules", "vendor",
    "/XF", "config.php", "erp-config.php", "mirror-config.php",
    "/NFL", "/NDL", "/NJH", "/NJS", "/nc", "/ns", "/np"
)
cmd /c ($robocopyArgs -join " ")
Write-ReportLine "- public_html copied to $InstallPath"

# Copy includes from repo root
$srcIncludes = Join-Path $RepoRoot "includes"
$destIncludes = Join-Path $InstallPath "includes"
if (Test-Path $srcIncludes) {
    if (-not (Test-Path $destIncludes)) { New-Item -ItemType Directory -Path $destIncludes -Force | Out-Null }
    Copy-Item (Join-Path $srcIncludes "*.php") $destIncludes -Force -ErrorAction SilentlyContinue
    Write-ReportLine "- repo includes synced"
}

# Copy tools/production runtime
$toolsDest = Join-Path $InstallPath "tools"
New-Item -ItemType Directory -Path $toolsDest -Force | Out-Null
Copy-Item (Join-Path $ScriptDir "*") $toolsDest -Force -ErrorAction SilentlyContinue
$templateSrc = Join-Path $RepoRoot "tools\desktop-run-templates"
if (Test-Path $templateSrc) {
    Copy-Item (Join-Path $templateSrc "*") $toolsDest -Force -ErrorAction SilentlyContinue
}

# 4. Config
$configPath = Join-Path $InstallPath "private\erp-config.php"
if ($CreateConfig -or -not (Test-Path $configPath)) {
    $cfgScript = Join-Path $RepoRoot "tools\desktop-run-templates\CREATE_LOCAL_CONFIG.ps1"
    if (Test-Path $cfgScript) {
        & $cfgScript -InstallPath $InstallPath -SqlServer $SqlServer -DatabaseName $DatabaseName -BaseUrl $BaseUrl
        Write-ReportLine "- Config generator executed"
    } else {
        Write-ReportLine "- WARN: CREATE_LOCAL_CONFIG.ps1 not found - configure private/erp-config.php manually"
    }
} else {
    Write-ReportLine "- Existing config preserved"
}

# 5. SQL migration (idempotent canonical bundle + verify)
if (-not $SkipSql) {
    Write-ReportLine "## SQL Migration"
    $sqlDir = Join-Path $RepoRoot "public_html\sql\sqlserver"
    $sqlCanonical = Join-Path $sqlDir "MOGHARE360_V1_CANONICAL_DATABASE.sql"
    $sqlVerify = Join-Path $sqlDir "MOGHARE360_V1_DATABASE_VERIFY.sql"
    $sqlcmd = Get-Command sqlcmd -ErrorAction SilentlyContinue
    if (-not $sqlcmd) {
        Write-ReportLine "- FAIL: sqlcmd not found - cannot apply canonical database"
        $Report | Set-Content -Path $ReportPath -Encoding UTF8
        exit 1
    }
    if (-not (Test-Path $sqlCanonical)) {
        Write-ReportLine "- FAIL: canonical SQL missing at $sqlCanonical"
        $Report | Set-Content -Path $ReportPath -Encoding UTF8
        exit 1
    }
    Push-Location $sqlDir
    try {
        & sqlcmd -S $SqlServer -d $DatabaseName -E -i $sqlCanonical -b 2>&1 | Out-Null
        if ($LASTEXITCODE -ne 0) {
            Write-ReportLine "- FAIL: MOGHARE360_V1_CANONICAL_DATABASE.sql exit $LASTEXITCODE"
            $Report | Set-Content -Path $ReportPath -Encoding UTF8
            exit 1
        }
        Write-ReportLine "- MOGHARE360_V1_CANONICAL_DATABASE.sql applied"
        if (Test-Path $sqlVerify) {
            $verifyOut = & sqlcmd -S $SqlServer -d $DatabaseName -E -i $sqlVerify -b 2>&1
            $verifyText = ($verifyOut | Out-String)
            if ($verifyText -match 'VERIFY RESULT:\s*FAIL') {
                Write-ReportLine "- FAIL: database verify reported missing tables"
                Write-ReportLine $verifyText
                $Report | Set-Content -Path $ReportPath -Encoding UTF8
                exit 1
            }
            Write-ReportLine "- MOGHARE360_V1_DATABASE_VERIFY.sql PASS"
        }
    } finally {
        Pop-Location
    }
}

# 6. Health checks
Write-ReportLine "## Health Checks"
$lintFiles = @(
    "includes\moghare360-release-package-helper.php",
    "moghare360-release-download.php",
    "saas-health.php"
)
foreach ($rel in $lintFiles) {
    $f = Join-Path $InstallPath $rel
    if (Test-Path $f) {
        & $phpExe -l $f 2>&1 | Out-Null
        $ok = ($LASTEXITCODE -eq 0)
        Write-ReportLine "- PHP lint $rel : $(if ($ok) {'PASS'} else {'FAIL'})"
    }
}

Write-ReportLine "- Local URL: $BaseUrl"
Write-ReportLine "- Release download: ${BaseUrl}moghare360-release-download.php"
Write-ReportLine "- SaaS health: ${BaseUrl}saas-health.php"
Write-ReportLine "- API health: ${BaseUrl}api/mirror/health.php"

Write-ReportLine ""
Write-ReportLine "Completed: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
Write-ReportLine "Rollback: restore from $backupDir if created"

$Report | Set-Content -Path $ReportPath -Encoding UTF8
Write-Host "INSTALL COMPLETE - report: $ReportPath" -ForegroundColor Green
