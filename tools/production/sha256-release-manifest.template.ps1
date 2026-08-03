<#
.SYNOPSIS
  TEMPLATE: Generate SHA256 inventory for a release folder (deployment manifest helper).

.DESCRIPTION
  Copy to private ops path as sha256-release-manifest.ps1 and replace PLACEHOLDER_* as needed.
  Writes a UTF-8 markdown/CSV-friendly list of relative paths + SHA256.
  Does not include file contents. Skip secrets, backups, APKs if present.

.NOTES
  Phase 7 — Production Preparation
#>

[CmdletBinding()]
param(
    [string] $ReleaseRoot = 'PLACEHOLDER_RELEASE_ROOT',
    [string] $OutFile = 'PLACEHOLDER_MANIFEST_OUT_PATH\sha256-manifest.md',

    # Relative path prefixes/extensions to skip
    [string[]] $SkipExtensions = @('.bak', '.apk', '.aab', '.p12', '.jks', '.pfx', '.env'),
    [string[]] $SkipNameContains = @('erp-config.php', 'keystore', 'secret', 'password')
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

if ($ReleaseRoot -match 'PLACEHOLDER_' -or $OutFile -match 'PLACEHOLDER_') {
    throw 'Replace PLACEHOLDER_* values before running. This is a template only.'
}

if (-not (Test-Path -LiteralPath $ReleaseRoot)) {
    throw "Release root not found: $ReleaseRoot"
}

$outDir = Split-Path -Parent $OutFile
if ($outDir -and -not (Test-Path -LiteralPath $outDir)) {
    New-Item -ItemType Directory -Path $outDir -Force | Out-Null
}

$rootFull = (Resolve-Path -LiteralPath $ReleaseRoot).Path
$files = Get-ChildItem -LiteralPath $rootFull -Recurse -File

$lines = New-Object System.Collections.Generic.List[string]
$lines.Add('# SHA256 Release Manifest') | Out-Null
$lines.Add('') | Out-Null
$lines.Add("GeneratedUtc: $((Get-Date).ToUniversalTime().ToString('o'))") | Out-Null
$lines.Add("ReleaseRoot: $rootFull") | Out-Null
$lines.Add('DatabaseTarget: moghare360_ERP') | Out-Null
$lines.Add('') | Out-Null
$lines.Add('| Relative path | Bytes | SHA256 |') | Out-Null
$lines.Add('|---------------|------:|--------|') | Out-Null

$skipped = 0
foreach ($f in $files) {
    $rel = $f.FullName.Substring($rootFull.Length).TrimStart('\', '/')
    $ext = $f.Extension.ToLowerInvariant()
    $skip = $false
    if ($SkipExtensions -contains $ext) { $skip = $true }
    foreach ($frag in $SkipNameContains) {
        if ($rel -like "*$frag*") { $skip = $true }
    }
    if ($skip) {
        $skipped++
        continue
    }

    $hash = (Get-FileHash -LiteralPath $f.FullName -Algorithm SHA256).Hash
    $relMd = $rel -replace '\\', '/'
    $lines.Add("| ``$relMd`` | $($f.Length) | ``$hash`` |") | Out-Null
}

$lines.Add('') | Out-Null
$lines.Add("SkippedFiles: $skipped (secrets/backups/apk patterns)") | Out-Null
$lines.Add('') | Out-Null
$lines.Add('Copy rows into docs/release/v1/DEPLOYMENT_MANIFEST_TEMPLATE.md for this release.') | Out-Null

$utf8NoBom = New-Object System.Text.UTF8Encoding $false
[System.IO.File]::WriteAllLines($OutFile, $lines.ToArray(), $utf8NoBom)

Write-Host "Wrote $OutFile"
Write-Host "Hashed $($lines.Count - 8) file rows (approx). Skipped=$skipped"
exit 0
