#Requires -Version 5.1
$ErrorActionPreference = "Continue"
Write-Host "MOGHARE360 — Desktop Run Launcher" -ForegroundColor Green
& "$PSScriptRoot\CHECK_REQUIREMENTS.ps1"
Start-Process "http://localhost:8080/moghare360/"
