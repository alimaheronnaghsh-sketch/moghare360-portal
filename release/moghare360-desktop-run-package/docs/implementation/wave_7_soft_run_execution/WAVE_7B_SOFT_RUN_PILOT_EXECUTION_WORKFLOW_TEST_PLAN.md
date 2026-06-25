# WAVE 7B — Soft Run Pilot Execution Workflow — Test Plan

## CLI Test

```text
C:\xampp\php\php.exe tools/test-wave-7b-soft-run-pilot-execution-workflow.php
```

Expected:

```text
WAVE 7B SOFT RUN PILOT EXECUTION WORKFLOW TEST PASSED
```

## Browser Tests

| URL | Check |
|-----|-------|
| `/moghare360/erp-soft-run-pilot-execution-board.php` | Workflow link per row |
| `/moghare360/erp-soft-run-pilot-execution-detail.php?execution_id=1` | Workflow link, history table |
| `/moghare360/erp-soft-run-pilot-execution-workflow.php?execution_id=1` | Current status, allowed transitions, form |

## Manual Validation

1. Workflow page loads with current status
2. Perform valid transition (e.g. STARTED → OBSERVED)
3. Success page shows old and new status
4. Detail page shows updated execution + new history row
5. Board shows updated status
6. Attempt invalid transition — controlled block message
7. Confirm no writes outside pilot execution tables

## Boundaries

No final delivery, delivery completion, public portal, payment/accounting, or legal e-signature activation.
