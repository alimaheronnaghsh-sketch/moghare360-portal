# WAVE 8B — Soft Run Findings Workflow — Test Plan

## CLI Test

```text
C:\xampp\php\php.exe tools/test-wave-8b-soft-run-finding-workflow.php
```

Expected: `WAVE 8B SOFT RUN FINDING WORKFLOW TEST PASSED`

## Browser Test

1. `erp-soft-run-finding-workflow.php?finding_id=1` — loads, shows current status and allowed transitions
2. Perform valid transition (e.g. OPEN → UNDER_REVIEW)
3. Success page shows old/new finding and corrective statuses
4. Detail page shows updated record and new history row
5. Board shows updated status
6. Invalid transition blocked with Persian error

## Boundaries

- No writes outside Soft Run finding tables
- No pilot execution writes
- No final delivery / delivery completion
