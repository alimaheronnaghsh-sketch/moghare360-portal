<#
.SYNOPSIS
  Builds an unsigned/debug TWA APK when Android SDK is available.
  Output: release-artifacts/android/ (gitignored)
#>
$ErrorActionPreference = 'Stop'
$root = Split-Path (Split-Path $PSScriptRoot -Parent) -Parent
if (-not (Test-Path (Join-Path $PSScriptRoot '..\twa-manifest.example.json'))) {
  $root = Split-Path $PSScriptRoot -Parent
}
$twaRoot = Split-Path $PSScriptRoot -Parent
$outDir = Join-Path $root 'release-artifacts\android'
New-Item -ItemType Directory -Force -Path $outDir | Out-Null

$gradlew = Join-Path $twaRoot 'gradlew.bat'
if (-not (Test-Path $gradlew)) {
  Write-Host 'NO_GRADLE_WRAPPER'
  Write-Host 'Generate the TWA project with Bubblewrap against twa-manifest.example.json (set PLACEHOLDER_HTTPS_ORIGIN first),'
  Write-Host 'or open android-twa in Android Studio and use Build > Build Bundle(s) / APK(s) > Debug.'
  Write-Host "Expected ignored output directory: $outDir"
  Write-Host 'UNSIGNED_APK_BUILD=deferred_until_sdk_and_host_configured'
  exit 0
}

& $gradlew -p $twaRoot assembleDebug
$apk = Get-ChildItem -Path (Join-Path $twaRoot 'app\build\outputs\apk\debug') -Filter '*.apk' -ErrorAction SilentlyContinue | Select-Object -First 1
if ($null -eq $apk) {
  throw 'Debug APK not found after assembleDebug'
}
$dest = Join-Path $outDir 'moghare360-twa-debug.apk'
Copy-Item $apk.FullName $dest -Force
Write-Host "WROTE $dest"
Write-Host 'UNSIGNED_APK_BUILD=ok'
