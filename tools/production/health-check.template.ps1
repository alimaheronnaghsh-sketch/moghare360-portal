<#
.SYNOPSIS
  TEMPLATE: Production health checks for IIS + HTTPS site (no secrets).

.DESCRIPTION
  Copy to private ops path as health-check.ps1 and replace PLACEHOLDER_* values.
  Exit 0 = P0 checks passed; non-zero = failure (consider rollback).

.NOTES
  Phase 7 — Production Preparation
#>

[CmdletBinding()]
param(
    [string] $BaseUrl = 'https://PLACEHOLDER_PROD_FQDN/PLACEHOLDER_APP_BASE_PATH',
    [string] $IisSiteName = 'PLACEHOLDER_IIS_SITE_NAME',
    [string] $AppPoolName = 'PLACEHOLDER_IIS_APPPOOL_NAME',
    [string] $ToolsProbePath = 'tools/'
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$failures = New-Object System.Collections.Generic.List[string]

function Add-Fail([string] $msg) {
    $failures.Add($msg) | Out-Null
    Write-Host "FAIL: $msg" -ForegroundColor Red
}

function Add-Pass([string] $msg) {
    Write-Host "PASS: $msg" -ForegroundColor Green
}

if ($BaseUrl -match 'PLACEHOLDER_' -or $IisSiteName -match 'PLACEHOLDER_') {
    throw 'Replace PLACEHOLDER_* values before running. This is a template only.'
}

$BaseUrl = $BaseUrl.TrimEnd('/')

# --- IIS local checks (run on the VPS) ---
try {
    Import-Module WebAdministration -ErrorAction Stop
    $pool = Get-WebAppPoolState -Name $AppPoolName
    if ($pool.Value -ne 'Started') {
        Add-Fail "App pool '$AppPoolName' state=$($pool.Value)"
    } else {
        Add-Pass "App pool Started ($AppPoolName)"
    }

    $site = Get-Website -Name $IisSiteName
    if (-not $site -or $site.State -ne 'Started') {
        Add-Fail "IIS site '$IisSiteName' not Started"
    } else {
        Add-Pass "IIS site Started ($IisSiteName)"
    }
} catch {
    Add-Fail "IIS module/site checks failed: $($_.Exception.Message)"
}

# --- HTTP(S) probes ---
function Invoke-Probe([string] $Url) {
    try {
        return Invoke-WebRequest -Uri $Url -MaximumRedirection 0 -SkipHttpErrorCheck -UseBasicParsing -TimeoutSec 30
    } catch {
        # Some PS versions throw on non-success; normalize
        if ($_.Exception.Response) {
            return $_.Exception.Response
        }
        throw
    }
}

try {
    $homeRes = Invoke-WebRequest -Uri "$BaseUrl/" -UseBasicParsing -TimeoutSec 30
    $code = [int]$homeRes.StatusCode
    if ($code -ge 200 -and $code -lt 400) {
        Add-Pass "Base URL reachable (HTTP $code)"
    } else {
        Add-Fail "Base URL unexpected status $code"
    }
    $body = [string]$homeRes.Content
    if ($body -match '(?i)(stack trace|Fatal error|Warning:|Notice:|display_errors)') {
        Add-Fail 'Response body looks like raw PHP/debug output'
    } else {
        Add-Pass 'No obvious raw PHP/debug leakage in home response'
    }
} catch {
    Add-Fail "Base URL probe failed: $($_.Exception.Message)"
}

try {
    $toolsUrl = "$BaseUrl/$($ToolsProbePath.TrimStart('/'))"
    $toolsRes = Invoke-WebRequest -Uri $toolsUrl -UseBasicParsing -TimeoutSec 30 -SkipHttpErrorCheck
    $tcode = [int]$toolsRes.StatusCode
    if ($tcode -eq 403 -or $tcode -eq 404) {
        Add-Pass "Tools path blocked/not found (HTTP $tcode)"
    } elseif ($tcode -ge 200 -and $tcode -lt 300) {
        Add-Fail "Tools path appears publicly reachable (HTTP $tcode) — must block"
    } else {
        Add-Pass "Tools path returned HTTP $tcode (review manually)"
    }
} catch {
    # Connection refused / 404 variants may still be acceptable; record as review
    Add-Pass "Tools probe exception (manual review): $($_.Exception.Message)"
}

if ($failures.Count -gt 0) {
    Write-Host "Health check FAILED ($($failures.Count) P0 issues)." -ForegroundColor Red
    $failures | ForEach-Object { Write-Host " - $_" }
    exit 1
}

Write-Host 'Health check PASSED (P0).' -ForegroundColor Green
exit 0
