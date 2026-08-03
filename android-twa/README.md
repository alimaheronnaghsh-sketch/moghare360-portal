# MOGHARE360 Android Trusted Web Activity (TWA)

Canonical business application = HTTPS PWA only.
This project wraps the same origin via Chrome Custom Tabs / TWA.
Do **not** embed ERP business logic, PHP, or API clients here.

## Application identity (defaults — Owner may change before release)

| Key | Value |
|-----|-------|
| applicationId | `ir.moghare360.app` |
| app name | `MOGHAREH360` |
| versionCode | integer, monotonic |
| versionName | align with V1 release tag (e.g. `1.0.0`) |
| host | Production HTTPS domain (Owner-provided) |

## Build

1. Install Android Studio (SDK 34+) and JDK 17.
2. Copy `keystore.properties.example` → `keystore.properties` (local only; gitignored via parent patterns).
3. Set `android-twa/gradle.properties` host URL to Production HTTPS origin.
4. Update `app/src/main/res/xml/asset_statements` / digital asset links after domain is known.
5. Debug unsigned APK:

```powershell
pwsh -File android-twa/scripts/build-debug.ps1
```

Output path (ignored): `release-artifacts/android/moghare360-twa-debug.apk`

## Signing

See `docs/release/v1/ANDROID_BUILD_SIGNING_FA.md`.
Never commit keystores, passwords, or signed AAB/APK.

## Capacitor

Not used. Required features (camera upload, PDF) are covered by the web app in Chrome/TWA.
If a native-only capability appears later, document justification before architecture change.
