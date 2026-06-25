# WAVE 9C — Executive Go/No-Go Decision Workflow — Test Plan

## CLI Test

```text
C:\xampp\php\php.exe tools/test-wave-9c-executive-go-no-go-decision-workflow.php
```

Expected: `WAVE 9C EXECUTIVE GO NO GO DECISION WORKFLOW TEST PASSED`

## CLI Coverage

- Workflow and submit pages exist
- Helper APIs: `allowed_transitions`, `validate_transition`, `update_workflow`, `validate_workflow_payload`
- Transition matrix (RECORDED→UNDER_REVIEW, CANCELLED terminal, ACCEPTED→CLOSED/ACTION_REQUIRED, CLOSED→UNDER_REVIEW)
- `change_reason` required
- Prepared UPDATE/INSERT statements; workflow does not INSERT new decision rows
- Write boundary (no findings/pilot/JobCard/delivery/etc.)
- Board/detail link to workflow; no POST on read-only pages
- No WAVE 9C SQL; WAVE 9B create submit unchanged
- No auth/config changes; WAVE 9A/6/7/8 helpers unchanged

## Manual Runtime Test (after copy to htdocs)

1. Open `erp-executive-go-no-go-decision-workflow.php?decision_id=1`
2. Verify current status RECORDED and allowed transitions displayed
3. Submit transition RECORDED → UNDER_REVIEW with `change_reason`
4. Confirm submit result shows old/new status and type
5. Open decision detail — verify status UNDER_REVIEW and new history row
6. Verify read-only transition review table on workflow page

## Out of Scope

- Creating new decision records (WAVE 9B create flow)
- Final delivery approval or production activation
