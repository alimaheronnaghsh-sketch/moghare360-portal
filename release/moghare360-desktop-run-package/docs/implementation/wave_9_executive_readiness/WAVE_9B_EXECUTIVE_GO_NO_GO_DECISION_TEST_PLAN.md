# WAVE 9B — Executive Go/No-Go Decision Log — Test Plan

## SQL (user SSMS)

Execute: `public_html/sql/wave_9b_executive_go_no_go_decision_log.sql` on `MOGHARE360_ERP`

## CLI Test

```text
C:\xampp\php\php.exe tools/test-wave-9b-executive-go-no-go-decision.php
```

Expected: `WAVE 9B EXECUTIVE GO NO GO DECISION TEST PASSED`

## Browser Test (after SQL + copy to htdocs)

- `http://localhost:8080/moghare360/erp-executive-go-no-go-decision-create.php`
- `http://localhost:8080/moghare360/erp-executive-go-no-go-decision-board.php`
- `http://localhost:8080/moghare360/erp-executive-soft-run-readiness-dashboard.php`

Verify: create one decision, success shows decision_id/code, board and detail show record, history row exists.

## Boundaries

Controlled internal decision log only. Cursor did not decide the next roadmap step.
