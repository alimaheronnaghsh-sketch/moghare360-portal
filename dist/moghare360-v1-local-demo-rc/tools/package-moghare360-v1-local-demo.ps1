#Requires -Version 5.1
param(
    [switch]$DryRun
)

$ErrorActionPreference = "Stop"

# Excludes: *.bak, *.backup, *.log, *.tmp, *.zip, private/, .env
# Output: dist/moghare360-v1-local-demo-rc/ + dist/moghare360-v1-local-demo-rc.zip

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$RepoRoot = Split-Path -Parent $ScriptDir

if (-not (Test-Path (Join-Path $RepoRoot "public_html"))) {
    Write-Error "public_html not found at repo root: $RepoRoot"
    exit 1
}

$DistRoot = Join-Path $RepoRoot "dist"
$PackageDir = Join-Path $DistRoot "moghare360-v1-local-demo-rc"
$ZipPath = Join-Path $DistRoot "moghare360-v1-local-demo-rc.zip"
$ManifestPath = Join-Path $PackageDir "MANIFEST.txt"
$HashPath = Join-Path $PackageDir "PACKAGE_SHA256.txt"

$ExcludedDirNames = @(
    'private', '.git', '.github', 'node_modules', 'vendor',
    'uploads', 'cache', 'temp', 'session', 'logs', 'backups',
    'release', 'dist'
)

$ExcludedFileNames = @(
    'config.php', 'mirror-config.php', 'erp-config.php',
    'config.example.php', 'erp-config.example.php'
)

$CredentialPatterns = @(
    '(?i)api[_-]?key\s*=\s*[''"][^''"]{6,}',
    '(?i)password\s*=\s*[''"][^''"]{8,}',
    '(?i)secret\s*=\s*[''"][^''"]{6,}',
    '(?i)bearer\s+[A-Za-z0-9\-_\.]{20,}',
    '(?i)token\s*=\s*[''"][^''"]{12,}'
)

function Get-M360NormalizedPath {
    param([string]$Path)
    return ($Path -replace '\\', '/').TrimStart('./').ToLowerInvariant()
}

function Test-M360ExcludedRelativePath {
    param([string]$RelativePath)

    $norm = Get-M360NormalizedPath $RelativePath
    if ($norm -eq '') { return $false }

    if ($norm -match '(^|/)\.env(\.|$|/)') { return $true }

    foreach ($dir in $ExcludedDirNames) {
        if ($norm -match "(^|/)$([regex]::Escape($dir))(/|$)") {
            return $true
        }
    }

    $leaf = [System.IO.Path]::GetFileName($norm.Replace('/', [System.IO.Path]::DirectorySeparatorChar))
    if ($ExcludedFileNames -contains $leaf) { return $true }
    if ($leaf -match '\.(bak|backup|tmp|log|zip)$') { return $true }

    return $false
}

function Copy-M360TreeSafe {
    param(
        [string]$SourceRoot,
        [string]$DestRoot,
        [string]$RelativePrefix = ''
    )

    if (-not (Test-Path $SourceRoot)) { return @() }

    $copied = @()
    $sourceFull = (Resolve-Path $SourceRoot).Path

    Get-ChildItem -Path $sourceFull -Recurse -File -Force | ForEach-Object {
        $relative = $_.FullName.Substring($sourceFull.Length).TrimStart('\', '/')
        if ($RelativePrefix -ne '') {
            $relative = ($RelativePrefix.TrimEnd('/') + '/' + $relative).TrimStart('/')
        }

        if (Test-M360ExcludedRelativePath $relative) { return }

        $target = Join-Path $DestRoot $relative
        $targetDir = Split-Path -Parent $target
        if (-not (Test-Path $targetDir)) {
            New-Item -ItemType Directory -Path $targetDir -Force | Out-Null
        }
        Copy-Item -LiteralPath $_.FullName -Destination $target -Force
        $copied += $relative
    }

    return $copied
}

function Test-M360CredentialScan {
    param([string[]]$Files)

    $suspects = @()
    foreach ($file in $Files) {
        if (-not (Test-Path $file)) { continue }
        $leaf = [System.IO.Path]::GetFileName($file).ToLowerInvariant()
        if ($leaf -eq 'package-moghare360-v1-local-demo.ps1') { continue }
        if ($leaf -like 'test-p*.php') { continue }

        $norm = Get-M360NormalizedPath $file.Replace($RepoRoot, '').TrimStart('/')
        $ext = [System.IO.Path]::GetExtension($file).ToLowerInvariant()
        if ($ext -notin @('.php', '.sql', '.md', '.txt', '.json')) { continue }

        $content = Get-Content -LiteralPath $file -Raw -ErrorAction SilentlyContinue
        if ($null -eq $content) { continue }

        $strictPatterns = @(
            '(?i)api[_-]?key\s*=\s*[''"][^''"]{6,}[''"]',
            '(?i)password\s*=\s*[''"][^''"]{8,}[''"]',
            '(?i)secret\s*=\s*[''"][^''"]{6,}[''"]',
            '(?i)bearer\s+[A-Za-z0-9\-_\.]{24,}',
            '(?i)sms[_-]?api[_-]?key\s*=\s*[''"][^''"]{8,}[''"]'
        )
        foreach ($pat in $strictPatterns) {
            if ($content -match $pat) {
                $suspects += "$file :: pattern $pat"
            }
        }

        $skipMobilePlate = $false
        if ($norm -match '(^|/)docs/missions(/|$)') { $skipMobilePlate = $true }
        if ($norm -match 'test-cases|validation-engine-test|TEST_RESULT') { $skipMobilePlate = $true }
        if ($ext -eq '.md') { $skipMobilePlate = $true }

        if (-not $skipMobilePlate) {
            if ($content -match '(?i)[''"]09[0-9]{9}[''"]' -and $content -notmatch '(?i)DEMO|M360-DEMO|09123456789|09121234567|09000000000') {
                $suspects += "$file :: quoted real-like mobile"
            }
            if ($content -match '(?i)[''"][0-9]{2}[A-Za-z\u0600-\u06FF]{1,3}[0-9]{3}[''"]' -and $content -notmatch '(?i)DEMO|M360-DEMO') {
                $suspects += "$file :: quoted real-like plate"
            }
        }
    }

    return $suspects
}

Write-Host "MOGHARE360 V1 Local Demo Package"
Write-Host "Repo: $RepoRoot"
Write-Host "DryRun: $DryRun"

if ($DryRun) {
    $scriptContent = Get-Content -LiteralPath $MyInvocation.MyCommand.Path -Raw
    $checks = @(
        ($scriptContent -match 'private'),
        ($scriptContent -match '\.env'),
        ($scriptContent -match 'config\.php'),
        ($scriptContent -match 'SHA256'),
        ($scriptContent -match 'CredentialPatterns|strictPatterns'),
        ($scriptContent -match 'exit 1')
    )
    if ($checks -contains $false) {
        Write-Error "DryRun self-check failed"
        exit 1
    }
    Write-Host "DRY RUN PASS - exclusions, credential scan, SHA256 hooks present"
    return
}

if (Test-Path $PackageDir) { Remove-Item $PackageDir -Recurse -Force }
if (Test-Path $ZipPath) { Remove-Item $ZipPath -Force }
New-Item -ItemType Directory -Path $PackageDir -Force | Out-Null

$allCopied = @()
$allCopied += Copy-M360TreeSafe -SourceRoot (Join-Path $RepoRoot "public_html") -DestRoot (Join-Path $PackageDir "public_html")
$allCopied += Copy-M360TreeSafe -SourceRoot (Join-Path $RepoRoot "database\migrations") -DestRoot (Join-Path $PackageDir "database\migrations")
$allCopied += Copy-M360TreeSafe -SourceRoot (Join-Path $RepoRoot "docs\release") -DestRoot (Join-Path $PackageDir "docs\release")
$allCopied += Copy-M360TreeSafe -SourceRoot (Join-Path $RepoRoot "docs\demo") -DestRoot (Join-Path $PackageDir "docs\demo")
if (Test-Path (Join-Path $RepoRoot "docs\missions")) {
    $allCopied += Copy-M360TreeSafe -SourceRoot (Join-Path $RepoRoot "docs\missions") -DestRoot (Join-Path $PackageDir "docs\missions")
}

$toolsDest = Join-Path $PackageDir "tools"
New-Item -ItemType Directory -Path $toolsDest -Force | Out-Null
Get-ChildItem (Join-Path $RepoRoot "tools") -Filter "test-p*.php" -File | ForEach-Object {
    if (-not (Test-M360ExcludedRelativePath $_.Name)) {
        Copy-Item -LiteralPath $_.FullName -Destination (Join-Path $toolsDest $_.Name) -Force
        $allCopied += "tools/$($_.Name)"
    }
}
Copy-Item -LiteralPath $MyInvocation.MyCommand.Path -Destination (Join-Path $toolsDest "package-moghare360-v1-local-demo.ps1") -Force
$allCopied += "tools/package-moghare360-v1-local-demo.ps1"

$manifestDoc = Join-Path $RepoRoot "docs\release\MOGHARE360_V1_LOCAL_DEMO_PACKAGE_MANIFEST.md"
if (Test-Path $manifestDoc) {
    Copy-Item -LiteralPath $manifestDoc -Destination (Join-Path $PackageDir "docs\release\MOGHARE360_V1_LOCAL_DEMO_PACKAGE_MANIFEST.md") -Force
}

$scanFiles = Get-ChildItem -Path $PackageDir -Recurse -File | ForEach-Object { $_.FullName }
$suspects = Test-M360CredentialScan -Files $scanFiles
if ($suspects.Count -gt 0) {
    Write-Host "PACKAGE BUILD BLOCKED - suspicious content:"
    $suspects | ForEach-Object { Write-Host $_ }
    Remove-Item $PackageDir -Recurse -Force -ErrorAction SilentlyContinue
    exit 1
}

$manifestLines = @(
    "MOGHARE360 V1 Local Demo RC Package",
    "Generated: $(Get-Date -Format o)",
    "Files: $($allCopied.Count)",
    "--- INCLUDED ---"
) + ($allCopied | Sort-Object)
$manifestLines | Set-Content -LiteralPath $ManifestPath -Encoding UTF8

Compress-Archive -Path (Join-Path $PackageDir "*") -DestinationPath $ZipPath -Force

$sha = (Get-FileHash -LiteralPath $ZipPath -Algorithm SHA256).Hash
Set-Content -LiteralPath $HashPath -Value $sha -Encoding UTF8

Write-Host "PACKAGE CREATED"
Write-Host "Dir: dist/moghare360-v1-local-demo-rc/"
Write-Host "Zip: dist/moghare360-v1-local-demo-rc.zip"
Write-Host "SHA256: $sha"
exit 0
