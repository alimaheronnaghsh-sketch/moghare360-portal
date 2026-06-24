# WAVE 8C — Soft Run Findings Review — Test Plan

## CLI Test

```text
C:\xampp\php\php.exe tools/test-wave-8c-soft-run-finding-review.php
```

Expected: `WAVE 8C SOFT RUN FINDING REVIEW TEST PASSED`

## Browser Test

1. `erp-soft-run-finding-review-dashboard.php` — review status, counts, history coverage, recent findings
2. `erp-soft-run-finding-board.php` — review dashboard nav link
3. `erp-soft-run-finding-detail.php?finding_id=1` — review dashboard nav link

## Boundaries

- No POST forms on dashboard
- No DB writes
- No final delivery / delivery completion
