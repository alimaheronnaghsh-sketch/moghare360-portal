<#
.SYNOPSIS
  TEMPLATE: Restore SQL Server database moghare360_ERP from a .bak file.

.DESCRIPTION
  Copy to a private ops path, rename to restore-db.ps1, replace PLACEHOLDER_* values.
  Prefer restoring to a rehearsal DB first. Production restore requires approval.
  No dual-writable topology — restore replaces the single app DB target you specify.

.NOTES
  Phase 7 — Production Preparation. No credentials in this template.
#>

[CmdletBinding()]
param(
    [string] $SqlInstance = 'PLACEHOLDER_SQL_INSTANCE',

    # Production target must remain moghare360_ERP. For rehearsal use PLACEHOLDER_REHEARSAL_DB_NAME in a private copy.
    [string] $Database = 'moghare360_ERP',

    [string] $BakPath = 'PLACEHOLDER_BACKUP_LOCAL_PATH\PLACEHOLDER_BACKUP_FILE.bak',

    # Safety gate: must pass explicitly for production-named DB
    [switch] $ConfirmProductionRestore
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

if ($BakPath -match 'PLACEHOLDER_' -or $SqlInstance -match 'PLACEHOLDER_') {
    throw 'Replace PLACEHOLDER_* values before running. This is a template only.'
}

if (-not (Test-Path -LiteralPath $BakPath)) {
    throw "Backup file not found: $BakPath"
}

if ($Database -eq 'moghare360_ERP' -and -not $ConfirmProductionRestore) {
    throw 'Refusing to restore moghare360_ERP without -ConfirmProductionRestore. For rehearsal, set -Database to a non-prod name in your private script copy.'
}

Write-Host "WARNING: Restoring [$Database] on [$SqlInstance] from $BakPath"
Write-Host 'Ensure app write traffic is frozen. Approver: PLACEHOLDER_ROLLBACK_APPROVER'

# Single-user + restore pattern (adjust paths/MOVE as needed for your file layout).
# Logical file names differ per backup — inspect with RESTORE FILELISTONLY in your private runbook.
$sql = @"
ALTER DATABASE [$Database] SET SINGLE_USER WITH ROLLBACK IMMEDIATE;
RESTORE DATABASE [$Database]
FROM DISK = N'$BakPath'
WITH REPLACE, STATS = 10;
ALTER DATABASE [$Database] SET MULTI_USER;
"@

$sqlcmdArgs = @(
    '-S', $SqlInstance,
    '-d', 'master',
    '-E',
    '-Q', $sql
)

& sqlcmd @sqlcmdArgs
if ($LASTEXITCODE -ne 0) {
    throw "sqlcmd restore failed with exit code $LASTEXITCODE"
}

Write-Host 'Restore command completed. Run health-check and smoke next.'
Write-Host 'Do not leave a second writable clone in dual-write mode.'
exit 0
