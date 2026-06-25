#Requires -Version 5.1
<#
.SYNOPSIS
  Build clean cPanel mirror ZIP for public site upload (no secrets, no nested junk).
#>
$ErrorActionPreference = "Stop"

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$RepoRoot = Split-Path -Parent $ScriptDir
. (Join-Path $ScriptDir "package-mogh-common.ps1")

$SourceDir = Join-Path $RepoRoot "release\moghare360-mirror-site-package\public_html"
$StageDir = Join-Path $RepoRoot "release\_mirror_clean_stage"
$ZipOut = Join-Path $RepoRoot "release\moghare360-cpanel-mirror-clean.zip"

if (-not (Test-Path $SourceDir)) {
    throw "Mirror source missing: $SourceDir"
}

if (Test-Path $StageDir) {
    Remove-Item -LiteralPath $StageDir -Recurse -Force
}
New-Item -ItemType Directory -Path $StageDir -Force | Out-Null

Copy-MoghTreeSafe -SourceRoot $SourceDir -DestRoot $StageDir -ExtraExcludedDirNames @('docs', 'runtime')

$forbidden = @('config.php', 'erp-config.php', 'mirror-config.php')
Get-ChildItem -Path $StageDir -Recurse -File -Force | ForEach-Object {
    if ($forbidden -contains $_.Name) {
        Remove-Item -LiteralPath $_.FullName -Force
    }
}

if (Test-Path $ZipOut) {
    Remove-Item -LiteralPath $ZipOut -Force
}

Add-Type -AssemblyName System.IO.Compression.FileSystem
[System.IO.Compression.ZipFile]::CreateFromDirectory($StageDir, $ZipOut)

Test-MoghZipArchive -ZipPath $ZipOut

Write-Host "CPANEL MIRROR CLEAN ZIP CREATED - PASS"
Write-Host "Path: release/moghare360-cpanel-mirror-clean.zip"
