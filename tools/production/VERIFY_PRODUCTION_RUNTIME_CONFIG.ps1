#Requires -Version 5.1
<#
.SYNOPSIS
  Verify MOGHARE360 V1 production runtime config, DB, and API health (TEST payload only).
#>
param(
    [string]$RepoRoot = "",
    [string]$InstallPath = "C:\xampp\htdocs\moghare360",
    [string]$BaseUrl = "",
    [switch]$SkipHttp
)

$ErrorActionPreference = "Stop"
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
if ($RepoRoot -eq "") {
    $RepoRoot = Split-Path (Split-Path $ScriptDir -Parent) -Parent
}

$phpExe = "C:\xampp\php\php.exe"
if (-not (Test-Path $phpExe)) {
    $phpCmd = Get-Command php -ErrorAction SilentlyContinue
    if ($phpCmd) { $phpExe = $phpCmd.Source }
}

$erpConfig = Join-Path $RepoRoot "private\erp-config.php"
$siteConfig = Join-Path $RepoRoot "private\production-site-config.json"
$siteTemplate = Join-Path $RepoRoot "private\templates\production-site-config.template.json"

$results = New-Object System.Collections.Generic.List[object]

function Add-Check([string]$Name, [bool]$Ok, [string]$Detail = "") {
    $script:results.Add([pscustomobject]@{ Name = $Name; Pass = $Ok; Detail = $Detail })
    $mark = if ($Ok) { 'PASS' } else { 'FAIL' }
    $suffix = if ($Detail -ne '') { " - $Detail" } else { '' }
    Write-Host "[$mark] $Name$suffix"
}

function Write-Utf8NoBom([string]$Path, [string]$Content) {
    [System.IO.File]::WriteAllText($Path, $Content, (New-Object System.Text.UTF8Encoding $false))
}

function Get-ConfigJsonFromPhp([string]$ConfigPath) {
    $escaped = $ConfigPath.Replace('\', '/')
    $php = @"
<?php
`$c = require '$escaped';
echo json_encode(`$c, JSON_UNESCAPED_UNICODE);
"@
    $tmp = [System.IO.Path]::GetTempFileName() + '.php'
    Write-Utf8NoBom -Path $tmp -Content $php
    try {
        return (& $phpExe $tmp) | ConvertFrom-Json
    } finally {
        Remove-Item -LiteralPath $tmp -Force -ErrorAction SilentlyContinue
    }
}

$siteConfigExists = Test-Path $siteConfig
Add-Check "private/erp-config.php exists" (Test-Path $erpConfig)
if ($siteConfigExists) {
    Add-Check "production-site-config.json exists (private)" $true
} else {
    Add-Check "production-site-config.json exists (private)" $false "template: $siteTemplate"
}

$erp = $null
$site = $null
if (Test-Path $erpConfig) {
    try {
        $erp = Get-ConfigJsonFromPhp -ConfigPath $erpConfig
        Add-Check "erp-config database section" ($null -ne $erp.database.server -and $erp.database.name -ne '')
    } catch {
        Add-Check "erp-config readable" $false $_.Exception.Message
    }
}

if (Test-Path $siteConfig) {
    try {
        $site = Get-Content -LiteralPath $siteConfig -Raw -Encoding UTF8 | ConvertFrom-Json
        Add-Check "site config domain present" ($site.domain -ne '')
        Add-Check "site config company_code" ($site.company_code -eq 'MOGHAREH_MAIN')
        Add-Check "site config master_server_base_url" ($site.master_server_base_url -match '^https?://')
        Add-Check "site config mirror_base_url" ($site.mirror_base_url -match '^https?://')
        Add-Check "site config ssl_expected flag" ($null -ne $site.ssl_expected)
        Add-Check "site config storage_path" ($site.storage_path -ne '')
    } catch {
        Add-Check "production-site-config.json parse" $false $_.Exception.Message
    }
}

if ($erp) {
    $server = [string]$erp.database.server
    $db = [string]$erp.database.name
    $trusted = [bool]$erp.database.trusted_connection
    $sqlcmd = Get-Command sqlcmd -ErrorAction SilentlyContinue
    if ($sqlcmd) {
        $args = @('-S', $server, '-d', $db, '-h', '-1', '-W', '-Q', 'SET NOCOUNT ON; SELECT COUNT(*) FROM dbo.erp_companies;')
        if ($trusted) { $args += '-E' } else { $args += @('-U', [string]$erp.database.username, '-P', [string]$erp.database.password) }
        $out = & sqlcmd @args 2>&1
        Add-Check "SQL Server connection" ($LASTEXITCODE -eq 0) "db=$db"
        if ($LASTEXITCODE -eq 0) {
            Add-Check "erp_companies reachable" ([int]($out | Select-Object -Last 1) -ge 1)
        }
    } else {
        Add-Check "sqlcmd available" $false
    }
}

if ($BaseUrl -eq '') {
    if ($erp -and $erp.saas.api_base_url) {
        $BaseUrl = [string]$erp.saas.api_base_url
    } elseif (Test-Path $siteConfig) {
        $site = Get-Content -LiteralPath $siteConfig -Raw -Encoding UTF8 | ConvertFrom-Json
        $BaseUrl = [string]$site.master_server_base_url
    } else {
        $BaseUrl = "http://localhost:8080/moghare360/"
    }
}
$BaseUrl = $BaseUrl.TrimEnd('/') + '/'

if (-not $SkipHttp) {
    try {
        $health = Invoke-WebRequest -Uri ($BaseUrl + 'saas-health.php') -UseBasicParsing -TimeoutSec 15
        Add-Check "saas-health HTTP" ($health.StatusCode -ge 200 -and $health.StatusCode -lt 500) "HTTP $($health.StatusCode)"
    } catch {
        Add-Check "saas-health HTTP" $false $_.Exception.Message
    }

    try {
        $mirror = Invoke-WebRequest -Uri ($BaseUrl + 'api/mirror/health.php') -UseBasicParsing -TimeoutSec 15
        Add-Check "api/mirror/health HTTP" ($mirror.StatusCode -ge 200 -and $mirror.StatusCode -lt 500) "HTTP $($mirror.StatusCode)"
    } catch {
        Add-Check "api/mirror/health HTTP" $false $_.Exception.Message
    }

    $testPayload = @{
        customer_name = 'TEST_V1_REAL_RUN_READINESS_DO_NOT_USE'
        mobile = '09000000000'
        vehicle_plate = '99-TEST-99'
        request_description = 'real-run-readiness-verify'
        source_channel = 'REAL_RUN_VERIFY'
    } | ConvertTo-Json -Compress

    try {
        $resp = Invoke-WebRequest -Uri ($BaseUrl + 'api/customer/request.php') -Method POST -Body $testPayload -ContentType 'application/json; charset=utf-8' -UseBasicParsing -TimeoutSec 20
        Add-Check "api/customer/request TEST write" ($resp.StatusCode -ge 200 -and $resp.StatusCode -lt 300) "HTTP $($resp.StatusCode)"
    } catch {
        $code = 0
        if ($_.Exception.Response) { $code = [int]$_.Exception.Response.StatusCode }
        Add-Check "api/customer/request TEST write" $false "HTTP $code"
    }
} else {
    Add-Check "HTTP checks skipped" $true "-SkipHttp"
}

$failed = @($results | Where-Object { -not $_.Pass })
$passed = $results.Count - $failed.Count
Write-Host ('-' * 60)
Write-Host "VERIFY RESULT: $passed/$($results.Count) PASS"
if ($failed.Count -gt 0) {
    exit 1
}
exit 0
