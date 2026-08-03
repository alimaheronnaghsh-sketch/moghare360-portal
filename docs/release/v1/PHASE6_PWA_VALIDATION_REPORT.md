# MOGHARE360 V1 — Phase 6 PWA / TWA Validation Report

**Branch:** `feature/workshop-service-sales`  
**Cache version:** `m360-pwa-static-v1-20260803`  
**Architecture:** Single canonical web app + optional TWA wrapper (`ir.moghare360.app`)

## Automated

See `tools/p360-pwa-security-uat.php` and `tools/_generated/v1-delivery/phase6-*.txt`.

## Manual / operator checklist

| Test | Expected |
|------|----------|
| Manifest validity | JSON loads; name/short_name/start_url/scope/display/icons |
| Installability (Chrome HTTPS or localhost) | beforeinstallprompt or Install app menu |
| SW registration | `service-worker.js` active; scope `./` |
| Update from old cache | activate deletes non-current cache keys |
| Obsolete-cache deletion | only `m360-pwa-static-v1-20260803` remains |
| Offline shell | `offline.html` when network fails |
| Protected page offline | no authenticated HTML from cache; offline shell |
| Logout sensitive absence | staff-logout clears Cache Storage |
| Session expiry | no-store on PHP; re-login works after SW update |
| Camera/photo upload | web input capture on HTTPS |
| PDF open/download | browser handler; not SW-cached |
| Deep link | start_url / query preserved in standalone |
| Browser back | history stack works |
| Android back (TWA) | Custom Tabs / TWA back to previous web history |
| Widths 390 / 768 / 1024 / 1366 / 1600 | public shell + design-system breakpoints |

## Security

- PHP, `/api/`, ERP, login, OTP, invoice, uploads: **network-only / no-store**
- Precache: offline shell + approved static assets only
- No APK/AAB/keystore in Git (`.gitignore`)

## Unsigned APK

`android-twa/scripts/build-debug.ps1` — deferred until Android SDK + Production HTTPS host configured; output under ignored `release-artifacts/android/`.
