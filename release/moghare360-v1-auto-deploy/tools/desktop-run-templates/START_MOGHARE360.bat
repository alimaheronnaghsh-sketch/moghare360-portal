@echo off
chcp 65001 >nul
echo MOGHARE360 — Starting local environment check...
powershell -ExecutionPolicy Bypass -File "%~dp0CHECK_REQUIREMENTS.ps1"
echo.
echo Opening browser: http://localhost:8080/moghare360/
start "" "http://localhost:8080/moghare360/"
pause
