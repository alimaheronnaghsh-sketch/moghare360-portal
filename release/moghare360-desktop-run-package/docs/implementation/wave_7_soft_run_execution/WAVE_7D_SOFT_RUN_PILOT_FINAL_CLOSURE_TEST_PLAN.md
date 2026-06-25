# WAVE 7D — Soft Run Pilot Final Closure — Test Plan

## CLI

```text
C:\xampp\php\php.exe tools/test-wave-7d-soft-run-pilot-final-closure.php
```

Expected: `WAVE 7D SOFT RUN PILOT FINAL CLOSURE TEST PASSED`

## Browser

| URL | Check |
|-----|-------|
| `/moghare360/erp-soft-run-pilot-final-closure-dashboard.php` | Final closure status, summaries, pages table |
| `/moghare360/erp-soft-run-pilot-review-dashboard.php` | Final closure nav link |
| `/moghare360/erp-soft-run-pilot-execution-board.php` | Final closure nav link |
| `/moghare360/erp-soft-run-pilot-execution-detail.php?execution_id=1` | Final closure nav link |

## Manual

- No POST forms on dashboard
- No DB writes
- All nav links work
