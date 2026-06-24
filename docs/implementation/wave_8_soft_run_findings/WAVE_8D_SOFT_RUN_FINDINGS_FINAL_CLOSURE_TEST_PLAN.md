# WAVE 8D — Soft Run Findings Final Closure — Test Plan

## CLI Test

```text
C:\xampp\php\php.exe tools/test-wave-8d-soft-run-finding-final-closure.php
```

Expected: `WAVE 8D SOFT RUN FINDING FINAL CLOSURE TEST PASSED`

## Checks

- Final closure helper and dashboard exist
- Required helper APIs present
- Helper uses SELECT only on Soft Run finding tables
- No INSERT/UPDATE/DELETE in helper
- Dashboard links to create, board, review, detail, workflow, WAVE 7 final closure, WAVE 7 pilot review
- Review/board/detail link to final closure dashboard (if modified)
- No POST form, file input, or delivery completion on dashboard
- No WAVE 8D SQL files
- No writes to pilot execution, JobCard, delivery, evidence, authorization, customer, vehicle, payment tables
- WAVE 7 helpers unchanged
- WAVE 8A/8B write helper and submit pages unchanged
- WAVE 8C review helper unchanged
- Documentation files exist

## Browser Test (after copy to htdocs)

- `http://localhost:8080/moghare360/erp-soft-run-finding-final-closure-dashboard.php`
- `http://localhost:8080/moghare360/erp-soft-run-finding-review-dashboard.php`
- `http://localhost:8080/moghare360/erp-soft-run-finding-board.php`
- `http://localhost:8080/moghare360/erp-soft-run-finding-detail.php?finding_id=1`

Verify: status visible, summaries visible, links work, no POST forms, product boundary banner present.

## Boundaries

This is read-only final closure/signoff dashboard only. No DB writes. Cursor did not decide the next roadmap step.
