# WAVE 9A — Executive Soft Run Readiness — Test Plan

## CLI Test

```text
C:\xampp\php\php.exe tools/test-wave-9a-executive-soft-run-readiness.php
```

Expected: `WAVE 9A EXECUTIVE SOFT RUN READINESS TEST PASSED`

## Browser Test (after copy to htdocs)

- `http://localhost:8080/moghare360/erp-executive-soft-run-readiness-dashboard.php`
- `http://localhost:8080/moghare360/erp-soft-run-control-room.php`
- `http://localhost:8080/moghare360/erp-soft-run-pilot-final-closure-dashboard.php`
- `http://localhost:8080/moghare360/erp-soft-run-finding-final-closure-dashboard.php`

## Boundaries

Read-only executive review only. No delivery approval. Cursor did not decide the next roadmap step.
