# MOGHARE360 V1 Owner Demo Runbook

**Audience:** Workshop owner / decision maker  
**Duration:** ~30–40 minutes (includes P10 RC review)  
**Prerequisite:** Staff login; P1–P10 migrations applied; P10 tests passing

## Pre-Demo Checklist

1. Log in via `staff-login.php`
2. Run P10 test suites (all PASS)
3. Open `erp-product-home.php` — confirm RC status badge
4. Confirm demo JobCard prefix `M360-DEMO` visible in P9 control center

## Demo Flow (P10-enhanced)

### 1. Product Home (P10) — 3 min

Open `erp-product-home.php`

- Show phase cards P2–P10
- Note Readiness Score and route coverage
- Explain: «این لایه فقط navigation است — workflow را تغییر نمی‌دهد»

### 2. Route Map & Link Audit (P10) — 5 min

1. `erp-route-map.php` — scroll P1–P10 table; highlight OK vs MISSING badges
2. `erp-link-audit.php` — confirm no missing critical routes (or document warnings)

### 3. Soft Run & E2E (P9) — 10 min

Follow `MOGHARE360_OWNER_DEMO_SCRIPT.md`:

1. `erp-soft-run-control-center.php`
2. `erp-end-to-end-demo-scenario.php`
3. `erp-demo-flow-map.php`

### 4. Management Visibility (P8) — 8 min

1. `erp-management-dashboard.php`
2. `erp-owner-control-center.php`
3. Optional: `erp-bottleneck-monitor.php`, `erp-financial-control-summary.php`

### 5. Release Readiness & RC Manifest (P10) — 7 min

1. `erp-release-readiness.php` — category table, score, blockers/warnings
2. `erp-demo-package-rc.php` — demo entry points, owner order, exclusions
3. Present recommendation from readiness report

### 6. Closing — 5 min

| Score / Status | Message to owner |
|----------------|------------------|
| PASS, ≥ 95% | Ready for owner demo RC presentation |
| WARNING only | Ready for soft run with documented gaps |
| BLOCKED | Fix blockers before proceeding |

## Owner Demo Order (Quick Reference)

1. `erp-product-home.php`
2. `erp-soft-run-control-center.php`
3. `erp-end-to-end-demo-scenario.php`
4. `erp-management-dashboard.php`
5. `erp-owner-control-center.php`
6. `erp-demo-package-rc.php`

## Do Not During Demo

- Share staff credentials or config secrets
- Use P10 pages to mutate JobCards, invoices, or payments
- Present WARNING items as production-ready without noting gaps
- Build or distribute zip packages from P10 UI

## After Demo

1. Note BLOCKED/WARNING items from `erp-release-readiness.php`
2. Operator completes `MOGHARE360_SOFT_RUN_OPERATOR_CHECKLIST.md`
3. Re-run all P10 tests + `test-v1-production-signoff.php` before next session

## Related Documents

- `docs/demo/MOGHARE360_OWNER_DEMO_SCRIPT.md`
- `docs/release/MOGHARE360_V1_DEMO_PACKAGE_RC.md`
- `docs/release/MOGHARE360_V1_RC_MANIFEST.md`
