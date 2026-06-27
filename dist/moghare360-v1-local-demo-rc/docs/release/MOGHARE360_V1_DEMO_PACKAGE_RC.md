# MOGHARE360 V1 Demo Package RC

**RC Version:** `MOGHARE360-V1-RC`  
**Package mode:** Manifest only — P10 does not build zip archives unless a pre-approved safe packaging tool exists.

## Demo Entry Points

Routes flagged `is_demo_entry` in `m360-navigation-registry.php`:

| Title | URL | Phase |
|-------|-----|-------|
| خانه محصول MOGHARE360 | `erp-product-home.php` | P10 |
| Demo Package RC | `erp-demo-package-rc.php` | P10 |
| کنترل‌سنتر Soft Run | `erp-soft-run-control-center.php` | P9 |
| سناریوی End-to-End Demo | `erp-end-to-end-demo-scenario.php` | P9 |
| چک‌لیست Soft Run | `erp-soft-run-checklist.php` | P9 |
| نقشه Demo Flow | `erp-demo-flow-map.php` | P9 |
| گزارش Demo Readiness | `erp-demo-readiness-report.php` | P9 |

## Owner Entry Points

Routes flagged `is_owner_entry` include P8 management dashboard, owner control center, operational KPI, and P10 release readiness.

## Owner Demo Order

1. `erp-product-home.php` — product overview and RC status
2. `erp-soft-run-control-center.php` — P9 readiness score
3. `erp-end-to-end-demo-scenario.php` — E2E scenario table
4. `erp-management-dashboard.php` — P8 KPI visibility
5. `erp-owner-control-center.php` — owner controls (read-only)
6. `erp-demo-package-rc.php` — RC manifest review

## Migrations Required Before Demo

All P1–P10 migrations listed in `MOGHARE360_V1_RC_MANIFEST.md` must be applied. P10 migration is informational only (no schema DDL).

## Tests to Run Before Demo

```text
C:\xampp\php\php.exe tools\test-p10-navigation-registry.php
C:\xampp\php\php.exe tools\test-p10-route-map.php
C:\xampp\php\php.exe tools\test-p10-link-audit.php
C:\xampp\php\php.exe tools\test-p10-release-hardening.php
C:\xampp\php\php.exe tools\test-p10-demo-package-rc.php
C:\xampp\php\php.exe tools\test-p10-security-scope-control.php
C:\xampp\php\php.exe tools\test-p10-production-signoff-integration.php
C:\xampp\php\php.exe tools\test-v1-production-signoff.php
```

## Package Build Policy

- **Zip build:** Disabled in P10 (`package_zip_available: false`)
- **Reason:** Avoid unsafe archive generation from web context
- **Alternative:** Use approved offline packaging tools outside P10 scope

## Operator Checklist

See also:

- `docs/demo/MOGHARE360_V1_OWNER_DEMO_RUNBOOK.md`
- `docs/demo/MOGHARE360_SOFT_RUN_OPERATOR_CHECKLIST.md`
- `docs/demo/MOGHARE360_OWNER_DEMO_SCRIPT.md`
