#Requires -Version 5.1
<#
.SYNOPSIS
  Build MOGHARE360 V1 clean runtime package from public_html (outside Git).
.NOTES
  Output under C:\MOGHARE360\packages\ — never commit generated ZIP/package.
#>
param(
  [string]$RepoRoot = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path,
  [string]$OutRoot = 'C:\MOGHARE360\packages',
  [string]$ReleaseHead = ''
)

$ErrorActionPreference = 'Stop'
Set-Location $RepoRoot

if (-not $ReleaseHead) {
  $ReleaseHead = (git rev-parse HEAD).Trim()
}
$short = $ReleaseHead.Substring(0, [Math]::Min(12, $ReleaseHead.Length))
$pkgName = "MOGHARE360_V1_RUNTIME_$short"
$pkgDir = Join-Path $OutRoot $pkgName
$webroot = Join-Path $pkgDir 'webroot'
$src = Join-Path $RepoRoot 'public_html'

if (-not (Test-Path $src)) { throw "Missing public_html at $src" }

foreach ($d in @($pkgDir, $webroot, (Join-Path $pkgDir 'sql'), (Join-Path $pkgDir 'deployment'), (Join-Path $pkgDir 'config-template'), (Join-Path $pkgDir 'manifest'), (Join-Path $pkgDir 'install-guide'))) {
  New-Item -ItemType Directory -Force -Path $d | Out-Null
}

# Clean prior webroot contents for idempotent rebuild
if (Test-Path $webroot) {
  Get-ChildItem $webroot -Force | Remove-Item -Recurse -Force -ErrorAction SilentlyContinue
}
New-Item -ItemType Directory -Force -Path $webroot | Out-Null

# Robocopy public_html -> webroot (flat mapping; no nested public_html)
$excludeDirs = @(
  '.git', '.github', 'node_modules', 'vendor', 'tests', 'test', '_generated',
  'uploads', 'storage', 'logs', 'backups', 'tmp', 'temp', 'quarantine',
  'release', 'dist', 'screenshots', 'coverage'
)
$xd = @()
foreach ($e in $excludeDirs) { $xd += @('/XD', $e) }

$excludeFiles = @(
  '*.zip', '*.apk', '*.aab', '*.keystore', '*.jks', '*.bak', '*.sql.bak',
  '*.map', '.env', '.env.*', '*otp-config.local.php', 'mirror-config.php',
  '*.secret.php', 'Thumbs.db', '.DS_Store'
)
$xf = @()
foreach ($e in $excludeFiles) { $xf += @('/XF', $e) }

& robocopy $src $webroot /E /NFL /NDL /NJH /NJS /nc /ns /np @xd @xf | Out-Null
$rc = $LASTEXITCODE
if ($rc -ge 8) { throw "robocopy failed code=$rc" }

# Guard: nested public_html must not exist
if (Test-Path (Join-Path $webroot 'public_html')) {
  throw 'NESTED_PUBLIC_HTML detected in package webroot'
}
if (-not (Test-Path (Join-Path $webroot 'index.php')) -and -not (Test-Path (Join-Path $webroot 'index.html'))) {
  throw 'Missing index entry in webroot'
}

# sql / deployment / config templates from repo (non-secret)
$sqlSrc = Join-Path $RepoRoot 'public_html\sql'
if (Test-Path $sqlSrc) {
  & robocopy $sqlSrc (Join-Path $pkgDir 'sql') /E /NFL /NDL /NJH /NJS /nc /ns /np | Out-Null
}
$depSrc = Join-Path $RepoRoot 'deployment'
if (Test-Path $depSrc) {
  & robocopy $depSrc (Join-Path $pkgDir 'deployment') /E /NFL /NDL /NJH /NJS /nc /ns /np | Out-Null
}
Get-ChildItem (Join-Path $RepoRoot 'private') -Filter '*.example.php' -EA SilentlyContinue | ForEach-Object {
  Copy-Item $_.FullName (Join-Path (Join-Path $pkgDir 'config-template') $_.Name) -Force
}
if (Test-Path (Join-Path $RepoRoot 'public_html\mirror-config.example.php')) {
  Copy-Item (Join-Path $RepoRoot 'public_html\mirror-config.example.php') (Join-Path (Join-Path $pkgDir 'config-template') 'mirror-config.example.php') -Force
}

# Install guides (copy FA docs subset)
$guideSrc = Join-Path $RepoRoot 'docs\release\v1'
$guideDst = Join-Path $pkgDir 'install-guide'
@(
  'PWA_INSTALL_GUIDE_FA.md','DEPLOYMENT_RUNBOOK_FA.md','PRODUCTION_PREREQUISITES_FA.md',
  'PRODUCTION_PACKAGE_INDEX.md'
) | ForEach-Object {
  $p = Join-Path $guideSrc $_
  if (Test-Path $p) { Copy-Item $p (Join-Path $guideDst $_) -Force }
}

# Size scan
function Get-DirMb([string]$path) {
  if (-not (Test-Path $path)) { return 0 }
  $sum = (Get-ChildItem $path -Recurse -File -EA SilentlyContinue | Measure-Object Length -Sum).Sum
  return [math]::Round(($sum / 1MB), 2)
}
$webMb = Get-DirMb $webroot
$totalMb = Get-DirMb $pkgDir
if ($webMb -gt 700) { throw "RUNTIME_PACKAGE_SIZE_BLOCKER webroot=${webMb}MB" }

# Secret / private scan (heuristic — ignore config key reads / placeholders)
$secretHits = @()
$patterns = '(?i)(password\s*=\s*[''"][^''"PLACEHOLDER][^''"]{7,}|AccountKey=[A-Za-z0-9+/=]{16,}|BEGIN (RSA |OPENSSH )?PRIVATE KEY|Server=.*?;.*Password=[^;]{4,}|smtp_pass\s*=\s*[''"][^''"]+|api_secret\s*=\s*[''"][^''"]+)'
Get-ChildItem $webroot -Recurse -Include *.php,*.js,*.json,*.env,*.config -File -EA SilentlyContinue | ForEach-Object {
  $c = Get-Content $_.FullName -Raw -EA SilentlyContinue
  if (-not $c) { return }
  if ($c -match '(?i)PLACEHOLDER|example\.php|changeme|YOUR_|\$database\[[''"]password[''"]]') {
    # still flag literal private keys
    if ($c -notmatch '(?i)BEGIN (RSA |OPENSSH )?PRIVATE KEY') {
      return
    }
  }
  if ($c -match $patterns) {
    $secretHits += $_.FullName.Substring($webroot.Length)
  }
}
if ($secretHits.Count -gt 0) {
  $secretHits | Set-Content (Join-Path $pkgDir 'manifest\SECRET_SCAN_HITS.txt') -Encoding UTF8
  throw "NO_SECRET_IN_PACKAGE failed hits=$($secretHits.Count)"
}

# Nested public_html scan already done
$nested = Test-Path (Join-Path $webroot 'public_html')

# VERSION / SHA lists
$ReleaseHead | Set-Content (Join-Path $pkgDir 'manifest\VERSION.txt') -Encoding ascii
@"
release_head=$ReleaseHead
built_at=$((Get-Date).ToString('o'))
webroot_mb=$webMb
package_mb=$totalMb
nested_public_html=$nested
"@ | Set-Content (Join-Path $pkgDir 'manifest\release-manifest.json') -Encoding UTF8

# SHA256SUMS for key files
$shaLines = @()
Get-ChildItem $webroot -Recurse -File -EA SilentlyContinue | ForEach-Object {
  $rel = $_.FullName.Substring($webroot.Length).TrimStart('\')
  $h = (Get-FileHash $_.FullName -Algorithm SHA256).Hash
  $shaLines += "$h  webroot/$($rel.Replace('\','/'))"
}
$shaLines | Set-Content (Join-Path $pkgDir 'manifest\SHA256SUMS.txt') -Encoding ascii

@"
# نصب بسته runtime تمیز MOGHARE360 V1

1. IIS physical path را روی محتویات پوشه ``webroot`` تنظیم کنید (نه روی ``public_html`` تو در تو).
2. پیکربندی خصوصی را بیرون از webroot از ``config-template`` بسازید.
3. آپلود و بکاپ را بیرون از webroot نگه دارید.
4. مهاجرت‌ها فقط CLI/SSMS — نه HTTP.
5. پس از استقرار، PWA و HTTPS را طبق راهنماهای ``install-guide`` بررسی کنید.

Release: $ReleaseHead
Webroot size: ${webMb} MB
"@ | Set-Content (Join-Path $pkgDir 'install-guide\INSTALL_FA.md') -Encoding UTF8

# ZIP
$zipPath = Join-Path $OutRoot "$pkgName.zip"
if (Test-Path $zipPath) { Remove-Item $zipPath -Force }
Compress-Archive -Path $pkgDir -DestinationPath $zipPath -Force
$zipHash = (Get-FileHash $zipPath -Algorithm SHA256).Hash
"$zipHash  $pkgName.zip" | Set-Content (Join-Path $OutRoot "$pkgName.zip.sha256") -Encoding ascii
$zipHash | Set-Content (Join-Path $pkgDir 'manifest\ZIP.sha256') -Encoding ascii

Write-Host "PACKAGE_OK dir=$pkgDir"
Write-Host "WEBROOT_MB=$webMb"
Write-Host "PACKAGE_MB=$totalMb"
Write-Host "ZIP=$zipPath"
Write-Host "ZIP_SHA256=$zipHash"
if ($webMb -gt 350) { Write-Host "WARN_OVER_TARGET_350MB=$webMb" }
