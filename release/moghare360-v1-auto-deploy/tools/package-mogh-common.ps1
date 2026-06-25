# MOGHARE360 — Shared packaging helpers (V1 release packages)
$script:MoghExcludedDirNames = @(
    'private', '.git', 'logs', 'backups', 'uploads',
    'node_modules', 'vendor', '.cursor'
)

$script:MoghExcludedFileNames = @(
    'config.php', 'erp-config.php', 'mirror-config.php',
    'moghare360_public_html_sanitized_backup.zip.zip'
)

function Get-MoghNormalizedPath {
    param([string]$Path)
    return ($Path -replace '\\', '/').TrimStart('./').ToLowerInvariant()
}

function Test-MoghExcludedRelativePath {
    param(
        [string]$RelativePath,
        [string[]]$ExtraExcludedDirNames = @(),
        [switch]$ExcludeReleaseSubdir
    )

    $norm = Get-MoghNormalizedPath $RelativePath
    if ($norm -eq '') { return $false }

    $allDirs = $script:MoghExcludedDirNames + $ExtraExcludedDirNames
    foreach ($dir in $allDirs) {
        if ($norm -match "(^|/)$([regex]::Escape($dir))(/|$)") {
            return $true
        }
    }

    if ($ExcludeReleaseSubdir -and ($norm -match '^release(/|$)' -or $norm -match '^public_html/release(/|$)')) {
        return $true
    }

    $leaf = [System.IO.Path]::GetFileName($norm.Replace('/', [System.IO.Path]::DirectorySeparatorChar))
    if ($script:MoghExcludedFileNames -contains $leaf) { return $true }
    if ($leaf -match '\.bak') { return $true }
    if ($leaf -match '\.(log|tmp)$') { return $true }
    if ($leaf -match '\.zip$' -and $leaf -notmatch 'package\.zip$') { return $true }

    return $false
}

function Copy-MoghTreeSafe {
    param(
        [string]$SourceRoot,
        [string]$DestRoot,
        [string[]]$ExtraExcludedDirNames = @(),
        [switch]$ExcludeReleaseSubdir
    )

    if (-not (Test-Path $SourceRoot)) { return }

    $sourceFull = (Resolve-Path $SourceRoot).Path
    Get-ChildItem -Path $sourceFull -Recurse -File -Force | ForEach-Object {
        $relative = $_.FullName.Substring($sourceFull.Length).TrimStart('\', '/')
        if (Test-MoghExcludedRelativePath -RelativePath $relative -ExtraExcludedDirNames $ExtraExcludedDirNames -ExcludeReleaseSubdir:$ExcludeReleaseSubdir) {
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

    foreach ($dir in $script:MoghExcludedDirNames) {
        if ($norm -match "(^|/)$([regex]::Escape($dir))(/|$)") {
            return $dir + '/'
        }
    }

    if ($norm -match '(^|/)uploads(/|$)' -or $norm -match '(^|/)backups(/|$)' -or $norm -match '(^|/)logs(/|$)') {
        return 'forbidden-path'
    }

    $leaf = [System.IO.Path]::GetFileName($norm.Replace('/', [System.IO.Path]::DirectorySeparatorChar))
    foreach ($name in $script:MoghExcludedFileNames) {
        if ($leaf -eq $name.ToLowerInvariant()) { return $name }
    }
    if ($leaf -eq 'mirror-config.php') { return 'mirror-config.php' }
    if ($leaf -match '\.bak') { return '.bak' }
    if ($leaf -match '\.log$') { return '.log' }
    if ($leaf -match '\.tmp$') { return '.tmp' }

    return $null
}

function Test-MoghZipArchive {
    param([string]$ZipPath)

    if (-not (Test-Path $ZipPath)) {
        throw "ZIP not found: $ZipPath"
    }

    Add-Type -AssemblyName System.IO.Compression.FileSystem
    $zip = [System.IO.Compression.ZipFile]::OpenRead($ZipPath)
    try {
        foreach ($entry in $zip.Entries) {
            $match = Test-MoghZipForbiddenEntry $entry.FullName
            if ($null -ne $match) {
                throw "FORBIDDEN CONTENT: $($entry.FullName) matched $match"
            }
        }
    } finally {
        $zip.Dispose()
    }
}

function Publish-MoghZip {
    param(
        [string]$PackageDir,
        [string]$ZipRepo,
        [string]$ZipWeb,
        [string]$ZipDownloads
    )

    if (Test-Path $ZipRepo) { Remove-Item $ZipRepo -Force }
    Compress-Archive -Path (Join-Path $PackageDir '*') -DestinationPath $ZipRepo -Force
    Test-MoghZipArchive -ZipPath $ZipRepo

    $webDir = Split-Path -Parent $ZipWeb
    if (-not (Test-Path $webDir)) {
        New-Item -ItemType Directory -Path $webDir -Force | Out-Null
    }
    Copy-Item -LiteralPath $ZipRepo -Destination $ZipWeb -Force

    $dlDir = Split-Path -Parent $ZipDownloads
    if (-not (Test-Path $dlDir)) {
        New-Item -ItemType Directory -Path $dlDir -Force | Out-Null
    }
    Copy-Item -LiteralPath $ZipRepo -Destination $ZipDownloads -Force
}
