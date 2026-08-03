<#
.SYNOPSIS
  TEMPLATE: Backup SQL Server database moghare360_ERP (no credentials in repo).

.DESCRIPTION
  Copy to a private ops path, rename to backup-db.ps1, and replace PLACEHOLDER_* values.
  Do not commit real passwords, connection strings with secrets, or backup files to git.
  Production host assumption: Windows VPS + SQL Server (not XAMPP).

.NOTES
  Phase 7 — Production Preparation
#>

[CmdletBinding()]
param(
    # e.g. PLACEHOLDER_SQL_INSTANCE  (.\SQLEXPRESS or host\instance)
    [string] $SqlInstance = 'PLACEHOLDER_SQL_INSTANCE',

    [string] $Database = 'moghare360_ERP',

    # Directory must exist and be outside web root
    [string] $BackupDirectory = 'PLACEHOLDER_BACKUP_LOCAL_PATH',

    # Optional offsite copy root (leave as placeholder to skip)
    [string] $OffsiteDirectory = 'PLACEHOLDER_BACKUP_OFFSITE_PATH',

    # Windows auth by default. For SQL auth, set env vars outside the script — never hard-code secrets.
    # $env:PLACEHOLDER_SQL_BACKUP_USER / use SQLCMD variables in your private wrapper.
    [switch] $UseWindowsAuth
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

if ($Database -ne 'moghare360_ERP') {
    throw "Refusing backup: database must be moghare360_ERP. Got: $Database"
}

if ($BackupDirectory -match 'PLACEHOLDER_' -or $SqlInstance -match 'PLACEHOLDER_') {
    throw 'Replace PLACEHOLDER_* values before running. This is a template only.'
}

if (-not (Test-Path -LiteralPath $BackupDirectory)) {
    throw "Backup directory does not exist: $BackupDirectory"
}

$stamp = Get-Date -Format 'yyyyMMdd_HHmmss'
$fileName = "moghare360_ERP_$stamp.bak"
$bakPath = Join-Path $BackupDirectory $fileName

Write-Host "Backing up [$Database] on [$SqlInstance] -> $bakPath"

# Prefer Windows Auth in production templates. Wire SQL auth only in a private wrapper.
# Example sqlcmd (Windows Auth):
$sql = @"
BACKUP DATABASE [$Database]
TO DISK = N'$bakPath'
WITH FORMAT, INIT, NAME = N'moghare360_ERP-full-$stamp', SKIP, NOREWIND, NOUNLOAD, STATS = 10;
"@

$sqlcmdArgs = @(
    '-S', $SqlInstance,
    '-d', 'master',
    '-E',
    '-Q', $sql
)

# If you must use SQL auth, DO NOT put the password here.
# Create a private wrapper that passes -U / -P from a secret store, or use Invoke-Sqlcmd with SecureString.

& sqlcmd @sqlcmdArgs
if ($LASTEXITCODE -ne 0) {
    throw "sqlcmd backup failed with exit code $LASTEXITCODE"
}

if (-not (Test-Path -LiteralPath $bakPath)) {
    throw "Backup file missing after sqlcmd: $bakPath"
}

$hash = (Get-FileHash -LiteralPath $bakPath -Algorithm SHA256).Hash
Write-Host "OK BackupId-ish file: $fileName"
Write-Host "SHA256: $hash"

if ($OffsiteDirectory -and ($OffsiteDirectory -notmatch 'PLACEHOLDER_')) {
    if (-not (Test-Path -LiteralPath $OffsiteDirectory)) {
        throw "Offsite directory does not exist: $OffsiteDirectory"
    }
    $dest = Join-Path $OffsiteDirectory $fileName
    Copy-Item -LiteralPath $bakPath -Destination $dest -Force
    Write-Host "Offsite copy: $dest"
} else {
    Write-Host 'Offsite copy skipped (PLACEHOLDER or empty). Copy manually to PLACEHOLDER_BACKUP_OFFSITE_PATH.'
}

Write-Host 'Done. Record PLACEHOLDER_BACKUP_ID / SHA256 in the deployment manifest.'
exit 0
