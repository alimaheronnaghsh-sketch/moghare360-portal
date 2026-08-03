<#
.SYNOPSIS
  TEMPLATE: Post-deploy smoke checks (HTTPS pages + security hygiene probes).

.DESCRIPTION
  Copy to private ops path as post-deploy-smoke.ps1 and replace PLACEHOLDER_* values.
  Does not log in with real credentials. Does not print secrets.

.NOTES
  Phase 7 — Production Preparation
#>

[CmdletBinding()]
param(
    [string] $BaseUrl = 'https://PLACEHOLDER_PROD_FQDN/PLACEHOLDER_APP_BASE_PATH',

    # Relative paths under BaseUrl (adjust to your release)
    [string[]] $SmokePaths = @(
        '/',
        'erp-product-home.php',
        'index.php'
    ),

    # Paths that must NOT be publicly OK (expect 403/404)
    [string[]] $MustBeBlocked = @(
        'tools/',
        'private/erp-config.php',
        '../private/erp-config.php'
    )
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

if ($BaseUrl -match 'PLACEHOLDER_') {
    throw 'Replace PLACEHOLDER_* values before running. This is a template only.'
}

$BaseUrl = $BaseUrl.TrimEnd('/')
$failures = New-Object System.Collections.Generic.List[string]

function Test-Url([string] $Url, [int[]] $OkCodes) {
    try {
        $res = Invoke-WebRequest -Uri $Url -UseBasicParsing -TimeoutSec 30 -SkipHttpErrorCheck
        return [int]$res.StatusCode
    } catch {
        if ($_.Exception.Response -and $_.Exception.Response.StatusCode) {
            return [int]$_.Exception.Response.StatusCode
        }
        throw
    }
}

Write-Host "Smoke against $BaseUrl"

foreach ($rel in $SmokePaths) {
    $url = if ($rel -eq '/' ) { "$BaseUrl/" } else { "$BaseUrl/$($rel.TrimStart('/'))" }
    try {
        $code = Test-Url -Url $url -OkCodes @(200, 302, 301, 303)
        if ($code -ge 200 -and $code -lt 400) {
            Write-Host "PASS smoke $rel -> HTTP $code"
            $page = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 30
            if ([string]$page.Content -match '(?i)(Fatal error|Uncaught|Stack trace)') {
                $failures.Add("Raw error content on $rel") | Out-Null
                Write-Host "FAIL raw error markers on $rel" -ForegroundColor Red
            }
        } else {
            $failures.Add("Smoke $rel -> HTTP $code") | Out-Null
            Write-Host "FAIL smoke $rel -> HTTP $code" -ForegroundColor Red
        }
    } catch {
        $failures.Add("Smoke $rel exception: $($_.Exception.Message)") | Out-Null
        Write-Host "FAIL smoke $rel: $($_.Exception.Message)" -ForegroundColor Red
    }
}

foreach ($rel in $MustBeBlocked) {
    $url = "$BaseUrl/$($rel.TrimStart('/'))"
    try {
        $code = Test-Url -Url $url -OkCodes @(403, 404)
        if ($code -eq 403 -or $code -eq 404) {
            Write-Host "PASS blocked $rel -> HTTP $code"
        } elseif ($code -ge 200 -and $code -lt 300) {
            $failures.Add("Should be blocked but OK: $rel") | Out-Null
            Write-Host "FAIL should be blocked: $rel -> HTTP $code" -ForegroundColor Red
        } else {
            Write-Host "REVIEW blocked probe $rel -> HTTP $code"
        }
    } catch {
        Write-Host "REVIEW blocked probe $rel: $($_.Exception.Message)"
    }
}

Write-Host 'Manual reminders (not automated here):'
Write-Host ' - Upload validation + private storage path PLACEHOLDER_PRIVATE_STORAGE_PATH'
Write-Host ' - Quarantine not public PLACEHOLDER_QUARANTINE_PATH'
Write-Host ' - Secure cookies / HTTPS-only session'
Write-Host ' - No XAMPP production dependency'

if ($failures.Count -gt 0) {
    Write-Host "Smoke FAILED ($($failures.Count))" -ForegroundColor Red
    $failures | ForEach-Object { Write-Host " - $_" }
    exit 1
}

Write-Host 'Smoke PASSED.' -ForegroundColor Green
exit 0
