# WAVE 7A — Soft Run Pilot Execution Log Foundation — Test Plan

## CLI Test

```text
C:\xampp\php\php.exe tools/test-wave-7a-soft-run-pilot-execution.php
```

Expected final output:

```text
WAVE 7A SOFT RUN PILOT EXECUTION TEST PASSED
```

## SQL Preconditions (SSMS)

1. Open `public_html/sql/wave_7a_soft_run_pilot_execution_log.sql` in SSMS.
2. Execute against `MOGHARE360_ERP`.
3. Confirm status row: `WAVE_7A_SOFT_RUN_PILOT_EXECUTION_SQL_FOUNDATION_READY`.

## Browser Tests (after SQL + copy to htdocs)

| URL | Check |
|-----|-------|
| `/moghare360/erp-soft-run-pilot-execution-create.php` | Page loads, boundary banner visible, form present |
| Submit form | Success shows `execution_id` and `execution_code` |
| `/moghare360/erp-soft-run-pilot-execution-board.php` | Created record visible, counts shown |
| `/moghare360/erp-soft-run-pilot-execution-detail.php?execution_id=N` | Record fields + history row |
| `/moghare360/erp-soft-run-final-closure-dashboard.php` | Nav links to create + board |
| `/moghare360/erp-soft-run-operator-test-pack.php` | Nav links to create + board |

## Validation Checklist

- [ ] Create page loads (Persian RTL)
- [ ] One controlled pilot execution record created
- [ ] History row exists for new execution
- [ ] Board is read-only (no POST form)
- [ ] Detail is read-only (no POST form)
- [ ] No write outside Soft Run pilot execution tables
- [ ] No final delivery action
- [ ] No delivery completion record
- [ ] No public portal / payment / accounting / legal e-signature activation
- [ ] WAVE 6A/6B/6C/6D helpers unchanged

## Boundaries

This wave does not perform final delivery, delivery completion, production login, public portal, payment, accounting, or legal e-signature activation.
