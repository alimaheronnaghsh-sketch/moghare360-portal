# WAVE 7C — Soft Run Pilot Review — Test Plan

## CLI

```text
C:\xampp\php\php.exe tools/test-wave-7c-soft-run-pilot-review.php
```

Expected: `WAVE 7C SOFT RUN PILOT REVIEW TEST PASSED`

## Browser

| URL | Check |
|-----|-------|
| `/moghare360/erp-soft-run-pilot-review-dashboard.php` | Status, counts, coverage, recent table |
| `/moghare360/erp-soft-run-pilot-execution-board.php` | Review dashboard link |
| `/moghare360/erp-soft-run-pilot-execution-detail.php?execution_id=1` | Review dashboard link |

## Manual

- No POST forms
- No DB writes from dashboard
- All navigation links work
- Boundary banner visible
