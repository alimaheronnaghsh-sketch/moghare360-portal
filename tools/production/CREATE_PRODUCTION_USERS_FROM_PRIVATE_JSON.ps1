#Requires -Version 5.1
<#
.SYNOPSIS
  Import production users from private/production-users.json into SQL Server (idempotent).
  Never logs plain passwords. Never reads templates as import source.
#>
param(
    [string]$RepoRoot = "",
    [string]$PrivateUsersFile = "",
    [string]$SqlServer = "",
    [string]$DatabaseName = "",
    [switch]$WhatIf
)

$ErrorActionPreference = "Stop"
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
if ($RepoRoot -eq "") {
    $RepoRoot = Split-Path (Split-Path $ScriptDir -Parent) -Parent
}

$phpExe = "C:\xampp\php\php.exe"
if (-not (Test-Path $phpExe)) {
    $phpCmd = Get-Command php -ErrorAction SilentlyContinue
    if ($phpCmd) { $phpExe = $phpCmd.Source } else { throw "PHP not found for password hashing." }
}

$templatePath = Join-Path $RepoRoot "private\templates\production-users.template.json"
if ($PrivateUsersFile -eq "") {
    $PrivateUsersFile = Join-Path $RepoRoot "private\production-users.json"
}

$runtimeDir = Join-Path $RepoRoot "runtime"
$reportPath = Join-Path $runtimeDir "PRODUCTION_USERS_IMPORT_REPORT.md"

$allowedRoles = @(
    'OWNER', 'SYSTEM_ADMIN', 'RECEPTION', 'TECHNICIAN', 'INVENTORY',
    'FINANCE', 'QC', 'CRM', 'COMPANY_OWNER_VIEWER'
)

$roleToCoreKey = @{
    OWNER = 'owner'
    SYSTEM_ADMIN = 'system_admin'
    RECEPTION = 'reception_staff'
    TECHNICIAN = 'mechanical_staff'
    INVENTORY = 'inventory_staff'
    FINANCE = 'finance_staff'
    QC = 'technical_manager'
    CRM = 'crm_staff'
    COMPANY_OWNER_VIEWER = 'read_only'
}

function Write-ReportLine([string]$Line) {
    $script:Report += $Line
    Write-Host $Line
}

function Escape-Sql([string]$Value) {
    return ($Value -replace "'", "''")
}

function Test-BcryptHash([string]$Value) {
    return $Value -match '^\$2[ayb]\$.{50,}$'
}

function Test-PlaceholderSecret([string]$Value) {
    if ([string]::IsNullOrWhiteSpace($Value)) { return $true }
    return ($Value -match '(?i)REPLACE_WITH|CHANGE_ME|PLACEHOLDER|EXAMPLE\.INVALID|YOUR_')
}

function Get-BcryptHashFromPlain([string]$PlainText) {
    $psi = New-Object System.Diagnostics.ProcessStartInfo
    $psi.FileName = $phpExe
    $psi.Arguments = '-r "echo password_hash(trim(stream_get_contents(`''php://stdin`'')), PASSWORD_BCRYPT);"'
    $psi.UseShellExecute = $false
    $psi.RedirectStandardInput = $true
    $psi.RedirectStandardOutput = $true
    $psi.RedirectStandardError = $true
    $psi.CreateNoWindow = $true
    $proc = [System.Diagnostics.Process]::Start($psi)
    $proc.StandardInput.Write($PlainText)
    $proc.StandardInput.Close()
    $hash = $proc.StandardOutput.ReadToEnd().Trim()
    $err = $proc.StandardError.ReadToEnd()
    $proc.WaitForExit()
    if ($proc.ExitCode -ne 0 -or $hash -eq '') {
        throw "password_hash generation failed: $err"
    }
    return $hash
}

function Write-Utf8NoBom([string]$Path, [string]$Content) {
    [System.IO.File]::WriteAllText($Path, $Content, (New-Object System.Text.UTF8Encoding $false))
}

function Get-ErpDatabaseConfig {
    param([string]$Root)
    $configPath = Join-Path $Root "private\erp-config.php"
    if (-not (Test-Path $configPath)) {
        throw "private/erp-config.php not found. Create it on the server before importing users."
    }
    $escaped = $configPath.Replace('\', '/')
    $php = @"
<?php
`$c = require '$escaped';
echo json_encode([
  'server' => (string)(`$c['database']['server'] ?? ''),
  'name' => (string)(`$c['database']['name'] ?? ''),
  'trusted' => !empty(`$c['database']['trusted_connection']),
  'username' => (string)(`$c['database']['username'] ?? ''),
  'password' => (string)(`$c['database']['password'] ?? ''),
], JSON_UNESCAPED_UNICODE);
"@
    $tmp = [System.IO.Path]::GetTempFileName() + '.php'
    Write-Utf8NoBom -Path $tmp -Content $php
    try {
        $json = & $phpExe $tmp
        return $json | ConvertFrom-Json
    } finally {
        Remove-Item -LiteralPath $tmp -Force -ErrorAction SilentlyContinue
    }
}

function Invoke-SqlScalar {
    param(
        [string]$Server,
        [string]$Database,
        [bool]$Trusted,
        [string]$Username,
        [string]$Password,
        [string]$Query
    )
    $args = @('-S', $Server, '-d', $Database, '-h', '-1', '-W', '-Q', $Query)
    if ($Trusted) { $args += '-E' } else { $args += @('-U', $Username, '-P', $Password) }
    $out = & sqlcmd @args 2>&1
    if ($LASTEXITCODE -ne 0) {
        throw "sqlcmd failed: $out"
    }
    return ($out | Where-Object { $_ -ne '' } | Select-Object -Last 1)
}

function Invoke-SqlNonQuery {
    param(
        [string]$Server,
        [string]$Database,
        [bool]$Trusted,
        [string]$Username,
        [string]$Password,
        [string]$Query
    )
    $args = @('-S', $Server, '-d', $Database, '-b', '-Q', $Query)
    if ($Trusted) { $args += '-E' } else { $args += @('-U', $Username, '-P', $Password) }
    $out = & sqlcmd @args 2>&1
    if ($LASTEXITCODE -ne 0) {
        throw "sqlcmd failed: $out"
    }
}

if (-not (Test-Path $PrivateUsersFile)) {
    Write-Host "FAIL: private production users file not found." -ForegroundColor Red
    Write-Host "Expected: $PrivateUsersFile"
    Write-Host "Template guide: $templatePath"
    Write-Host "Copy private/templates/production-users.template.json to private/production-users.json on the server only."
    exit 1
}

$sqlcmd = Get-Command sqlcmd -ErrorAction SilentlyContinue
if (-not $sqlcmd) {
    throw "sqlcmd not found. Install SQL Server command-line tools."
}

$dbCfg = Get-ErpDatabaseConfig -Root $RepoRoot
if ($SqlServer -eq "") { $SqlServer = [string]$dbCfg.server }
if ($DatabaseName -eq "") { $DatabaseName = [string]$dbCfg.name }
$trusted = [bool]$dbCfg.trusted
$dbUser = [string]$dbCfg.username
$dbPass = [string]$dbCfg.password

$payload = Get-Content -LiteralPath $PrivateUsersFile -Raw -Encoding UTF8 | ConvertFrom-Json
if (-not $payload.users) {
    throw "production-users.json has no users array."
}

if (-not (Test-Path $runtimeDir)) {
    New-Item -ItemType Directory -Path $runtimeDir -Force | Out-Null
}

$Report = @()
Write-ReportLine "# MOGHARE360 Production Users Import Report"
Write-ReportLine ""
Write-ReportLine "Started: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
Write-ReportLine "Source: private/production-users.json (private - not committed)"
Write-ReportLine "Database: $DatabaseName @ $SqlServer"
Write-ReportLine "WhatIf: $WhatIf"
Write-ReportLine ""

$created = 0
$updated = 0
$failed = 0

foreach ($user in @($payload.users)) {
    $username = [string]$user.username
    $display = [string]$user.display_name
    $mobile = [string]$user.mobile_optional
    $roleCode = [string]$user.role_code
    $companyCode = [string]$user.company_code
    $loginEnabled = [bool]$user.is_login_enabled
    $userId = [int]$user.user_id
    $secret = [string]$user.temporary_password_or_hash_placeholder

    if ($username -eq '' -or $display -eq '' -or $roleCode -eq '' -or $companyCode -eq '') {
        Write-ReportLine "- FAIL $username : missing required fields"
        $failed++
        continue
    }

    if ($allowedRoles -notcontains $roleCode) {
        Write-ReportLine "- FAIL $username : unsupported role_code $roleCode"
        $failed++
        continue
    }

    if (Test-PlaceholderSecret $secret) {
        Write-ReportLine "- FAIL $username : password/hash placeholder not replaced in private file"
        $failed++
        continue
    }

    try {
        if (Test-BcryptHash $secret) {
            $hash = $secret
        } else {
            $hash = Get-BcryptHashFromPlain -PlainText $secret
        }
    } catch {
        Write-ReportLine "- FAIL $username : could not prepare password hash"
        $failed++
        continue
    }

    $companyId = [int](Invoke-SqlScalar -Server $SqlServer -Database $DatabaseName -Trusted $trusted -Username $dbUser -Password $dbPass -Query "SET NOCOUNT ON; SELECT company_id FROM dbo.erp_companies WHERE company_code = N'$(Escape-Sql $companyCode)' AND is_active = 1;")
    if ($companyId -le 0) {
        Write-ReportLine "- FAIL $username : company_code not found ($companyCode)"
        $failed++
        continue
    }

    $coreRoleKey = $roleToCoreKey[$roleCode]
    $coreRoleId = [int](Invoke-SqlScalar -Server $SqlServer -Database $DatabaseName -Trusted $trusted -Username $dbUser -Password $dbPass -Query "SET NOCOUNT ON; SELECT role_id FROM dbo.core_roles WHERE role_key = N'$(Escape-Sql $coreRoleKey)' AND is_active = 1;")
    if ($coreRoleId -le 0) {
        Write-ReportLine "- FAIL $username : core role mapping missing ($coreRoleKey)"
        $failed++
        continue
    }

    $lifecycle = if ($loginEnabled) { 'ACTIVE' } else { 'INACTIVE' }
    $isOwner = if ($roleCode -eq 'OWNER') { 1 } else { 0 }
    $loginBit = if ($loginEnabled) { 1 } else { 0 }
    $hashSql = Escape-Sql $hash
    $userSql = Escape-Sql $username
    $displaySql = Escape-Sql $display
    $mobileSql = Escape-Sql $mobile
    $roleSql = Escape-Sql $roleCode

    $existsByUser = Invoke-SqlScalar -Server $SqlServer -Database $DatabaseName -Trusted $trusted -Username $dbUser -Password $dbPass -Query "SET NOCOUNT ON; SELECT COUNT(*) FROM dbo.core_users WHERE username = N'$userSql';"
    $existsById = Invoke-SqlScalar -Server $SqlServer -Database $DatabaseName -Trusted $trusted -Username $dbUser -Password $dbPass -Query "SET NOCOUNT ON; SELECT COUNT(*) FROM dbo.core_users WHERE user_id = $userId;"
    $action = 'noop'

    if ([int]$existsByUser -gt 0) {
        $existingId = [int](Invoke-SqlScalar -Server $SqlServer -Database $DatabaseName -Trusted $trusted -Username $dbUser -Password $dbPass -Query "SET NOCOUNT ON; SELECT user_id FROM dbo.core_users WHERE username = N'$userSql';")
        if ($existingId -ne $userId) {
            Write-ReportLine "- FAIL $username : username exists for different user_id ($existingId)"
            $failed++
            continue
        }
        $action = 'updated'
        $userSqlBatch = @"
SET NOCOUNT ON;
UPDATE dbo.core_users
SET full_name = N'$displaySql',
    mobile = CASE WHEN N'$mobileSql' = N'' THEN mobile ELSE N'$mobileSql' END,
    password_hash = N'$hashSql',
    lifecycle_state = N'$lifecycle',
    is_system_owner = $isOwner,
    is_login_enabled = $loginBit,
    updated_at = SYSUTCDATETIME()
WHERE user_id = $userId;
"@
    } elseif ([int]$existsById -gt 0) {
        Write-ReportLine "- FAIL $username : user_id $userId already used by another username"
        $failed++
        continue
    } else {
        $action = 'created'
        $userSqlBatch = @"
SET NOCOUNT ON;
INSERT INTO dbo.core_users (
    user_id, username, password_hash, full_name, mobile,
    lifecycle_state, is_system_owner, is_login_enabled, created_at
) VALUES (
    $userId, N'$userSql', N'$hashSql', N'$displaySql',
    CASE WHEN N'$mobileSql' = N'' THEN NULL ELSE N'$mobileSql' END,
    N'$lifecycle', $isOwner, $loginBit, SYSUTCDATETIME()
);
"@
    }

    $companyUserBatch = @"
SET NOCOUNT ON;
IF EXISTS (SELECT 1 FROM dbo.erp_company_users WHERE company_id = $companyId AND user_id = $userId)
    UPDATE dbo.erp_company_users
    SET role_code = N'$roleSql', is_active = $loginBit
    WHERE company_id = $companyId AND user_id = $userId;
ELSE
    INSERT INTO dbo.erp_company_users (company_id, user_id, role_code, is_active)
    VALUES ($companyId, $userId, N'$roleSql', $loginBit);
"@

    if (-not $WhatIf) {
        Invoke-SqlNonQuery -Server $SqlServer -Database $DatabaseName -Trusted $trusted -Username $dbUser -Password $dbPass -Query $userSqlBatch
        Invoke-SqlNonQuery -Server $SqlServer -Database $DatabaseName -Trusted $trusted -Username $dbUser -Password $dbPass -Query $companyUserBatch
    }

    if ($action -eq 'created') { $created++ } elseif ($action -eq 'updated') { $updated++ }
    Write-ReportLine "- OK $username : $action | role=$roleCode | company=$companyCode | core_role_key=$coreRoleKey"
}

Write-ReportLine ""
Write-ReportLine "Summary: created=$created updated=$updated failed=$failed"
Write-ReportLine "Completed: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
Write-ReportLine "Note: passwords are never written to this report."

$Report | Set-Content -Path $reportPath -Encoding UTF8

if ($failed -gt 0) {
    Write-Host "IMPORT COMPLETED WITH FAILURES - see $reportPath" -ForegroundColor Yellow
    exit 1
}

Write-Host "IMPORT PASS - report: $reportPath" -ForegroundColor Green
exit 0
