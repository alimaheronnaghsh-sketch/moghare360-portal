#Requires -Version 5.1
<#
.SYNOPSIS
  Create local mirror-config.php on XAMPP runtime (not committed to Git).
#>
param(
    [string]$InstallPath = "C:\xampp\htdocs\moghare360",
    [string]$MasterBaseUrl = "http://localhost:8080/moghare360",
    [switch]$Force
)

$ErrorActionPreference = "Stop"
$configPath = Join-Path $InstallPath "mirror-config.php"

if ((Test-Path $configPath) -and -not $Force) {
    Write-Host "mirror-config.php already exists: $configPath" -ForegroundColor Yellow
    Write-Host "Use -Force to overwrite."
    exit 0
}

$escapedUrl = $MasterBaseUrl.Replace("'", "''")
$content = @"
<?php
declare(strict_types=1);

/**
 * Local runtime mirror config — generated on destination only. DO NOT commit.
 */

return [
    'MASTER_SERVER_BASE_URL' => '$escapedUrl',
    'MIRROR_MODE' => true,
    'LOCAL_STORAGE_ALLOWED' => false,
    'HOST_DATABASE_ALLOWED' => false,
    'API_TIMEOUT_SECONDS' => 15,
    'BRAND_NAME' => 'مقاره موتورز',
    'SUPPORT_PHONE' => '021-00000000',
    'SMS_OTP_ENABLED' => false,
    'SMS_GATEWAY_CONFIGURED' => false,
];
"@

Set-Content -Path $configPath -Value $content -Encoding UTF8
# Rewrite without BOM (PHP strict_types requires first statement)
[System.IO.File]::WriteAllText($configPath, $content, (New-Object System.Text.UTF8Encoding $false))
Write-Host "[OK] Created $configPath" -ForegroundColor Green
Write-Host "MASTER_SERVER_BASE_URL = $MasterBaseUrl"
