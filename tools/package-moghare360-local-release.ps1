#Requires -Version 5.1
$ErrorActionPreference = "Stop"

# MOGHARE360 — Local Release Candidate 1 packaging (no secrets, no private config)
# Exclusions: *.bak, *.log, *.tmp, *.zip, private/, .git/, logs/, backups/, uploads/, release/
# config.php, config.example.php, private/erp-config.php, private/erp-config.example.php
# moghare360_public_html_sanitized_backup.zip.zip — real customer data warning

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$RepoRoot = Split-Path -Parent $ScriptDir

if (-not (Test-Path (Join-Path $RepoRoot "public_html"))) {
    Write-Error "Run from repo root context. public_html not found at: $RepoRoot"
    exit 1
}

$ReleaseRoot = Join-Path $RepoRoot "release"
$PackageDir = Join-Path $ReleaseRoot "moghare360-local-rc1"
$ZipRepo = Join-Path $ReleaseRoot "moghare360-local-rc1.zip"
$ZipWebDir = Join-Path $RepoRoot "public_html\release"
$ZipWeb = Join-Path $ZipWebDir "moghare360-local-rc1.zip"

$ExcludedDirNames = @(
    'private', '.git', 'logs', 'backups', 'uploads',
    'node_modules', 'vendor'
)

$ExcludedFileNames = @(
    'config.php', 'config.example.php', 'erp-config.php', 'erp-config.example.php',
    'moghare360_public_html_sanitized_backup.zip.zip'
)

function Get-MoghNormalizedPath {
    param([string]$Path)
    return ($Path -replace '\\', '/').TrimStart('./').ToLowerInvariant()
}

function Test-MoghExcludedRelativePath {
    param([string]$RelativePath)

    $norm = Get-MoghNormalizedPath $RelativePath
    if ($norm -eq '') { return $false }

    foreach ($dir in $ExcludedDirNames) {
        if ($norm -match "(^|/)$([regex]::Escape($dir))(/|$)") {
            return $true
        }
    }

    $leaf = [System.IO.Path]::GetFileName($norm.Replace('/', [System.IO.Path]::DirectorySeparatorChar))
    if ($ExcludedFileNames -contains $leaf) {
        return $true
    }

    if ($leaf -match '\.bak') { return $true }
    if ($leaf -match '\.(log|tmp|zip)$') { return $true }

    return $false
}

function Copy-MoghTreeSafe {
    param(
        [string]$SourceRoot,
        [string]$DestRoot,
        [string[]]$ExtraExcludedDirNames = @(),
        [switch]$ExcludeReleaseSubdir
    )

    if (-not (Test-Path $SourceRoot)) {
        return
    }

    $allExcludedDirs = $ExcludedDirNames + $ExtraExcludedDirNames
    $sourceFull = (Resolve-Path $SourceRoot).Path

    Get-ChildItem -Path $sourceFull -Recurse -File -Force | ForEach-Object {
        $relative = $_.FullName.Substring($sourceFull.Length).TrimStart('\', '/')
        $norm = Get-MoghNormalizedPath $relative

        foreach ($dir in $allExcludedDirs) {
            if ($norm -match "(^|/)$([regex]::Escape($dir))(/|$)") {
                return
            }
        }

        if ($ExcludeReleaseSubdir -and $norm -match '^release(/|$)') {
            return
        }

        if (Test-MoghExcludedRelativePath $relative) {
            return
        }

        $target = Join-Path $DestRoot $relative
        $targetDir = Split-Path -Parent $target
        if (-not (Test-Path $targetDir)) {
            New-Item -ItemType Directory -Path $targetDir -Force | Out-Null
        }
        Copy-Item -LiteralPath $_.FullName -Destination $target -Force
    }
}

function Test-MoghZipForbiddenEntry {
    param([string]$EntryName)

    $norm = Get-MoghNormalizedPath $EntryName
    if ($norm -eq '') { return $null }

    foreach ($dir in $ExcludedDirNames) {
        if ($norm -match "(^|/)$([regex]::Escape($dir))(/|$)") {
            return $dir + '/'
        }
    }

    if ($norm -match '^release(/|$)' -or $norm -match '^public_html/release(/|$)') {
        return 'release/'
    }

    $leaf = [System.IO.Path]::GetFileName($norm.Replace('/', [System.IO.Path]::DirectorySeparatorChar))
    foreach ($name in $ExcludedFileNames) {
        if ($leaf -eq $name.ToLowerInvariant()) {
            return $name
        }
    }

    if ($leaf -match '\.bak') { return '.bak' }
    if ($leaf -match '\.log$') { return '.log' }
    if ($leaf -match '\.tmp$') { return '.tmp' }

    return $null
}

function Test-MoghZipArchive {
    param([string]$ZipPath)

    if (-not (Test-Path $ZipPath)) {
        Write-Error "ZIP not found: $ZipPath"
        exit 1
    }

    $entries = @(& tar -tf $ZipPath 2>&1)
    if ($LASTEXITCODE -ne 0) {
        Write-Error "tar -tf failed for: $ZipPath"
        exit 1
    }

    foreach ($entry in $entries) {
        $match = Test-MoghZipForbiddenEntry $entry
        if ($null -ne $match) {
            Write-Host "FORBIDDEN CONTENT FOUND"
            Write-Host "ZIP: $ZipPath"
            Write-Host "Entry: $entry"
            Write-Host "Matched: $match"
            exit 1
        }
    }
}

if (Test-Path $PackageDir) { Remove-Item $PackageDir -Recurse -Force }
if (Test-Path $ZipRepo) { Remove-Item $ZipRepo -Force }
if (Test-Path $ZipWeb) { Remove-Item $ZipWeb -Force }

New-Item -ItemType Directory -Path $ReleaseRoot -Force | Out-Null
New-Item -ItemType Directory -Path $PackageDir -Force | Out-Null

Copy-MoghTreeSafe -SourceRoot (Join-Path $RepoRoot "public_html") -DestRoot (Join-Path $PackageDir "public_html") -ExcludeReleaseSubdir
Copy-MoghTreeSafe -SourceRoot (Join-Path $RepoRoot "docs") -DestRoot (Join-Path $PackageDir "docs")

$toolsDest = Join-Path $PackageDir "tools"
New-Item -ItemType Directory -Path $toolsDest -Force | Out-Null
Get-ChildItem (Join-Path $RepoRoot "tools") -Filter "test-phase-*.php" -File -ErrorAction SilentlyContinue | ForEach-Object {
    if (-not (Test-MoghExcludedRelativePath $_.Name)) {
        Copy-Item -LiteralPath $_.FullName -Destination (Join-Path $toolsDest $_.Name) -Force
    }
}

$releaseNotes = Join-Path $RepoRoot "docs\release\MOGHARE360_LOCAL_RC1_RELEASE_NOTES.md"
if (Test-Path $releaseNotes) {
    Copy-Item -LiteralPath $releaseNotes -Destination (Join-Path $PackageDir "RELEASE_NOTES.md") -Force
}

Compress-Archive -Path (Join-Path $PackageDir "*") -DestinationPath $ZipRepo -Force
Test-MoghZipArchive -ZipPath $ZipRepo

New-Item -ItemType Directory -Path $ZipWebDir -Force | Out-Null
Copy-Item -LiteralPath $ZipRepo -Destination $ZipWeb -Force

Write-Host "LOCAL RELEASE PACKAGE CREATED"
Write-Host "Path: release/moghare360-local-rc1.zip"
Write-Host "Web copy: public_html/release/moghare360-local-rc1.zip"
Write-Host "Exclusions verified"
