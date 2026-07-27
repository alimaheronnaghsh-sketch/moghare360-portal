#Requires -Version 5.1
param(
    [string]$Source = "C:\Users\User\Documents\GitHub\alimaheronnaghsh-sketch\moghare360-portal",
    [string]$Target = "C:\xampp\htdocs\moghare360",
    [string]$ManifestPath = "",
    [switch]$DryRun,
    [switch]$RemoveStale
)

$ErrorActionPreference = "Stop"

if ($ManifestPath -eq "") {
    $ManifestPath = Join-Path ([Environment]::GetFolderPath("MyDocuments")) "MOGHARE360_LANE0A_RUNTIME_MANIFEST.sha256.tsv"
}

$publicSource = Join-Path $Source "public_html"
if (-not (Test-Path -LiteralPath $publicSource)) {
    throw "public_html not found at $publicSource"
}

if (-not (Test-Path -LiteralPath $Target) -and -not $DryRun) {
    New-Item -ItemType Directory -Path $Target -Force | Out-Null
}

function Convert-ToRelativePath([string]$Root, [string]$Path) {
    return $Path.Substring($Root.Length).TrimStart("\") -replace "\\", "/"
}

function Test-ExcludedPublicPath([string]$RelativePath) {
    $rel = $RelativePath -replace "\\", "/"
    $lower = $rel.ToLowerInvariant()

    if ($lower -match '(^|/)(docs|tools|database|release|dist|node_modules|vendor|private|storage|uploads|sql)(/|$)') { return $true }
    if ($lower -match '(^|/)\.git(attributes|ignore|modules)?$') { return $true }
    if ($lower -match '(^|/)(config|config\.example|mirror-config|mirror-config\.example)\.php$') { return $true }
    if ($lower -match '(^|/)error_log$|\.log$|\.sql$|\.zip$|\.bak|\.old$|\.tmp$') { return $true }
    if ($lower -match '(^|/)receive-test\.php$|debug|probe|phpinfo|sandbox|mock') { return $true }
    if ($lower -match '(^|/).*test.*\.php$') { return $true }

    return $false
}

function Test-PreservedRuntimePath([string]$RelativePath) {
    $lower = ($RelativePath -replace "\\", "/").ToLowerInvariant()

    if ($lower -match '^(uploads|storage|private)(/|$)') { return $true }
    if ($lower -eq ".htaccess") { return $true }
    if ($lower -in @("config.php", "mirror-config.php")) { return $true }

    return $false
}

$deployFiles = New-Object System.Collections.Generic.List[object]

Get-ChildItem -LiteralPath $publicSource -Recurse -File -Force | ForEach-Object {
    $rel = Convert-ToRelativePath $publicSource $_.FullName
    if (-not (Test-ExcludedPublicPath $rel)) {
        $deployFiles.Add([pscustomobject]@{ Source = $_.FullName; RelativePath = $rel }) | Out-Null
    }
}

$supportFiles = @(
    "api\workflow-transition.php",
    "erp-access-request-create.php",
    "erp-access-request-detail.php",
    "erp-access-request-list.php",
    "erp-admin-dashboard.php",
    "erp-admin-login.php",
    "erp-admin-logout.php",
    "includes\erp-access-denied-handler.php",
    "includes\erp-audit-helper.php",
    "includes\erp-auth-context.php",
    "includes\erp-auth-helper.php",
    "includes\erp-config-loader.php",
    "includes\erp-csrf.php",
    "includes\erp-csrf-helper.php",
    "includes\erp-permission-check.php",
    "includes\erp-permission-guard.php",
    "includes\erp-permission-helper.php",
    "includes\erp-workflow-engine.php"
)

foreach ($rel in $supportFiles) {
    $sourcePath = Join-Path $Source $rel
    if (Test-Path -LiteralPath $sourcePath) {
        $deployFiles.Add([pscustomobject]@{ Source = $sourcePath; RelativePath = ($rel -replace "\\", "/") }) | Out-Null
    }
}

$allow = @{}
foreach ($file in $deployFiles) {
    $allow[$file.RelativePath.ToLowerInvariant()] = $true
}

Write-Host "MOGHARE360 controlled local deploy"
Write-Host "Source public_html: $publicSource"
Write-Host "Target: $Target"
Write-Host "DryRun: $DryRun"
Write-Host "RemoveStale: $RemoveStale"
Write-Host "Approved file count: $($deployFiles.Count)"

foreach ($file in $deployFiles) {
    $destination = Join-Path $Target ($file.RelativePath -replace "/", "\")
    if ($DryRun) {
        Write-Host "DRY-RUN COPY $($file.RelativePath)"
        continue
    }

    $parent = Split-Path -Parent $destination
    if (-not (Test-Path -LiteralPath $parent)) {
        New-Item -ItemType Directory -Path $parent -Force | Out-Null
    }
    Copy-Item -LiteralPath $file.Source -Destination $destination -Force
}

if ($RemoveStale -and (Test-Path -LiteralPath $Target)) {
    $runtimeFiles = Get-ChildItem -LiteralPath $Target -Recurse -File -Force
    foreach ($runtimeFile in $runtimeFiles) {
        $rel = Convert-ToRelativePath $Target $runtimeFile.FullName
        $key = $rel.ToLowerInvariant()
        if ($allow.ContainsKey($key) -or (Test-PreservedRuntimePath $rel)) {
            continue
        }

        if ($DryRun) {
            Write-Host "DRY-RUN REMOVE $rel"
        } else {
            Remove-Item -LiteralPath $runtimeFile.FullName -Force
            Write-Host "REMOVE $rel"
        }
    }

    if (-not $DryRun) {
        Get-ChildItem -LiteralPath $Target -Recurse -Directory -Force |
            Sort-Object FullName -Descending |
            Where-Object {
                $rel = Convert-ToRelativePath $Target $_.FullName
                -not (Test-PreservedRuntimePath $rel) -and
                @(Get-ChildItem -LiteralPath $_.FullName -Force).Count -eq 0
            } |
            Remove-Item -Force
    }
}

$manifestLines = foreach ($file in ($deployFiles | Sort-Object RelativePath)) {
    $pathForHash = if ($DryRun) { $file.Source } else { Join-Path $Target ($file.RelativePath -replace "/", "\") }
    if (Test-Path -LiteralPath $pathForHash) {
        $hash = (Get-FileHash -Algorithm SHA256 -LiteralPath $pathForHash).Hash
        "$hash`t$($file.RelativePath)"
    }
}

$manifestParent = Split-Path -Parent $ManifestPath
if ($manifestParent -and -not (Test-Path -LiteralPath $manifestParent) -and -not $DryRun) {
    New-Item -ItemType Directory -Path $manifestParent -Force | Out-Null
}

if ($DryRun) {
    Write-Host "DRY-RUN manifest path: $ManifestPath"
} else {
    $manifestLines | Set-Content -LiteralPath $ManifestPath -Encoding UTF8
    Write-Host "Manifest: $ManifestPath"
}

Write-Host "Controlled local deploy complete."
