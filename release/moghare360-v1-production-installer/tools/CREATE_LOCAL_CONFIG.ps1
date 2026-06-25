#Requires -Version 5.1
<#
.SYNOPSIS
  MOGHARE360 V1 — Create local config.php on destination system only.
  Never stores credentials inside ZIP packages.
#>
param(
    [string]$InstallPath = "C:\xampp\htdocs\moghare360",
    [string]$SqlServer = ".\SQLEXPRESS",
    [string]$DatabaseName = "moghare360_ERP",
    [string]$BaseUrl = "http://localhost:8080/moghare360/",
    [switch]$TrustedConnection = $true,
    [string]$DbUsername = "",
    [string]$DbPassword = "",
    [switch]$Force
)

$ErrorActionPreference = "Stop"

function Write-Step([string]$Message) {
    Write-Host $Message -ForegroundColor Cyan
}

$privateDir = Join-Path $InstallPath "private"
$configPath = Join-Path $privateDir "erp-config.php"
$examplePath = Join-Path $privateDir "erp-config.example.php"
$repoExample = Join-Path (Split-Path (Split-Path $PSScriptRoot -Parent) -Parent) "private\erp-config.example.php"

if (-not (Test-Path $privateDir)) {
    New-Item -ItemType Directory -Path $privateDir -Force | Out-Null
}

if ((Test-Path $configPath) -and -not $Force) {
    Write-Host "Config already exists: $configPath" -ForegroundColor Yellow
    Write-Host "Use -Force to overwrite after backup."
    exit 0
}

if (Test-Path $configPath) {
    $backup = "$configPath.backup_" + (Get-Date -Format "yyyyMMdd_HHmmss")
    Copy-Item -LiteralPath $configPath -Destination $backup -Force
    Write-Step "Backed up existing config to $backup"
}

$trusted = if ($TrustedConnection) { "true" } else { "false" }
$escapedUser = $DbUsername.Replace("'", "''")
$escapedPass = $DbPassword.Replace("'", "''")
$escapedServer = $SqlServer.Replace("'", "''")
$escapedDb = $DatabaseName.Replace("'", "''")
$escapedUrl = $BaseUrl.Replace("'", "''")

$content = @"
<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP local config — generated on destination system.
 * DO NOT commit this file.
 */

return [
    'environment' => 'production',
    'debug' => false,

    'database' => [
        'server' => '$escapedServer',
        'name' => '$escapedDb',
        'driver' => 'odbc',
        'trusted_connection' => $trusted,
        'username' => '$escapedUser',
        'password' => '$escapedPass',
    ],

    'security' => [
        'display_errors_to_browser' => false,
        'log_errors_internally' => true,
    ],

    'saas' => [
        'enabled' => true,
        'default_company_code' => 'MOGHAREH_MAIN',
        'storage_root' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage',
        'api_base_url' => '$escapedUrl',
        'mirror_allowed_origins' => ['https://moghareh360.ir', 'https://www.moghareh360.ir'],
    ],
];
"@

Set-Content -Path $configPath -Value $content -Encoding UTF8
Write-Host "[OK] Created $configPath" -ForegroundColor Green
Write-Host "Base URL: $BaseUrl"
Write-Host "SQL Server: $SqlServer / DB: $DatabaseName"
