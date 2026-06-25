#Requires -Version 5.1
$ErrorActionPreference = "Stop"

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$RepoRoot = Split-Path -Parent $ScriptDir
. (Join-Path $ScriptDir "package-mogh-common.ps1")

$PackageDir = Join-Path $RepoRoot "release\moghare360-mirror-site-package"
$ZipRepo = Join-Path $RepoRoot "release\moghare360-mirror-site-package.zip"
$ZipWeb = Join-Path $RepoRoot "public_html\release\moghare360-mirror-site-package.zip"
$ZipDownloads = Join-Path $env:USERPROFILE "Downloads\moghare360-mirror-site-package.zip"

$brandSrc = Join-Path $RepoRoot "public_html\assets\moghare360-brand\moghareh-motors-logo.jpg"
$brandDest = Join-Path $PackageDir "public_html\assets\brand"
New-Item -ItemType Directory -Path $brandDest -Force | Out-Null
if (Test-Path $brandSrc) {
    Copy-Item $brandSrc (Join-Path $brandDest "moghareh-motors-logo.jpg") -Force
}

$phpExe = "C:\xampp\php\php.exe"
if (-not (Test-Path $phpExe)) { $phpExe = "php" }

& $phpExe (Join-Path $RepoRoot "tools\generate-mirror-pwa-icons.php")
if ($LASTEXITCODE -ne 0) {
    Write-Warning "PWA icon generation failed - continuing if icons exist"
    $iconsDir = Join-Path $PackageDir "public_html\assets\icons"
    New-Item -ItemType Directory -Path $iconsDir -Force | Out-Null
    try {
        Add-Type -AssemblyName System.Drawing
        foreach ($size in @(192, 512)) {
            $iconPath = Join-Path $iconsDir "icon-$size.png"
            if (-not (Test-Path $iconPath)) {
                $bmp = New-Object System.Drawing.Bitmap $size, $size
                $g = [System.Drawing.Graphics]::FromImage($bmp)
                $g.Clear([System.Drawing.Color]::FromArgb(15, 23, 20))
                $brush = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(34, 197, 94))
                $g.FillRectangle($brush, [int]($size * 0.12), [int]($size * 0.12), [int]($size * 0.76), [int]($size * 0.76))
                $bmp.Save($iconPath, [System.Drawing.Imaging.ImageFormat]::Png)
                $g.Dispose()
                $bmp.Dispose()
            }
        }
    } catch {
        Write-Warning "System.Drawing icon fallback failed"
    }
}

if (-not (Test-Path (Join-Path $PackageDir "public_html\index.php"))) {
    throw "Mirror source missing at release/moghare360-mirror-site-package"
}

Publish-MoghZip -PackageDir $PackageDir -ZipRepo $ZipRepo -ZipWeb $ZipWeb -ZipDownloads $ZipDownloads

Write-Host "MIRROR SITE PACKAGE CREATED - PASS"
Write-Host "Path: release/moghare360-mirror-site-package.zip"
