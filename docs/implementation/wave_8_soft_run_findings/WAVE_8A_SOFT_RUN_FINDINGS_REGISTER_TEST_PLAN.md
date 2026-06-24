# WAVE 8A — Soft Run Findings Register — Test Plan

## CLI Test

```text
C:\xampp\php\php.exe tools/test-wave-8a-soft-run-finding-register.php
```

Expected final output: `WAVE 8A SOFT RUN FINDINGS REGISTER TEST PASSED`

## Checks

- SQL file exists, idempotent, no DROP
- Helper APIs present
- Helper writes only to `erp_soft_run_findings` and `erp_soft_run_finding_history`
- Prepared statements for INSERT
- Type/severity/status validation
- Submit POST-only
- Board/detail read-only (no POST)
- No final delivery / delivery completion
- No public portal / payment / accounting / legal e-signature activation
- WAVE 6 helpers unchanged
- WAVE 7 helpers and submit pages unchanged
- Optional navigation on pilot final closure and review dashboards
- Documentation files exist

## Browser Test (after SSMS SQL execution and htdocs copy)

1. `http://localhost:8080/moghare360/erp-soft-run-finding-create.php` — create page loads
2. Submit one controlled finding record
3. Success page shows `finding_id` and `finding_code`
4. `http://localhost:8080/moghare360/erp-soft-run-finding-board.php` — record visible
5. Detail page shows record and history row
6. `http://localhost:8080/moghare360/erp-soft-run-pilot-final-closure-dashboard.php` — finding nav links
7. `http://localhost:8080/moghare360/erp-soft-run-pilot-review-dashboard.php` — finding nav links

## Manual Validation

- No write outside Soft Run finding tables from WAVE 8A pages
- No POST on board/detail
- No final delivery action
