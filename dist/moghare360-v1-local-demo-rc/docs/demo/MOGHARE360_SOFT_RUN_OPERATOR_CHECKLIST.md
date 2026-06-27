# MOGHARE360 Soft Run Operator Checklist

Use before owner demo or internal soft run. All P9 UI requires staff login.

## Pre-flight (foundation)

- [ ] Config has no plaintext credentials in repo
- [ ] Migrations P1–P9 present; P9 applied (`erp_soft_run_*` tables)
- [ ] `test-v1-production-signoff.php` available in tools
- [ ] Demo JobCard exists with **`M360-DEMO`** prefix
- [ ] `staff-login.php` / `owner-login.php` unchanged

## Workflow pages reachable

- [ ] P1 — `erp-reception-online-requests.php`
- [ ] P1.5 — `erp-intake-contracts.php`
- [ ] P2 — `erp-reception-jobcards.php`
- [ ] P3 — `erp-technical-board.php`
- [ ] P4 — `erp-estimate-board.php`
- [ ] P5 — `erp-work-execution-board.php`
- [ ] P6 — `erp-qc-board.php`
- [ ] P7 — `erp-final-invoice-board.php`
- [ ] P8 — `erp-management-dashboard.php`

## Soft run UI

- [ ] Open `erp-soft-run-control-center.php` — readiness score loads
- [ ] Open `erp-end-to-end-demo-scenario.php` — stages list with evidence
- [ ] Open `erp-demo-flow-map.php` — operational links work
- [ ] Open `erp-demo-readiness-report.php` — score and migration flags
- [ ] Update checklist in `erp-soft-run-checklist.php` (optional; soft_run table only)

## Security / scope

- [ ] No gate bypass language in P9 code paths
- [ ] APIs return JSON on GET only
- [ ] No payment gateway or accounting voucher in P9 scope
- [ ] Checklist POST uses CSRF token

## Readiness targets

| Score | Recommendation |
|-------|----------------|
| &lt; 70% | Fix BLOCKED items before demo |
| 70–89% | Owner soft run acceptable with noted WARNINGs |
| ≥ 90%, 0 BLOCKED | Ready for internal demo |

## Automated verification

Run all P9 test suites:

```bat
cd moghare360-portal
C:\xampp\php\php.exe tools\test-p9-soft-run-schema.php
C:\xampp\php\php.exe tools\test-p9-soft-run-ui.php
C:\xampp\php\php.exe tools\test-p9-demo-scenario-flow.php
C:\xampp\php\php.exe tools\test-p9-e2e-gate-validation.php
C:\xampp\php\php.exe tools\test-p9-demo-data-safety.php
C:\xampp\php\php.exe tools\test-p9-management-dashboard-integration.php
C:\xampp\php\php.exe tools\test-p9-readiness-report.php
C:\xampp\php\php.exe tools\test-p9-scope-security.php
```

All must exit 0 before sign-off.
