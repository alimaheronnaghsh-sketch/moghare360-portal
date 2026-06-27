# Testing Plan

## Steps
1. Open `erp-service-operation-workbench-ux.php?role=service`
2. Verify KPI and operation list
3. Open detail UX for service_operation_id=1
4. Open board UX — verify columns and cards
5. Open technician workflow — test with/without assigned_to_user_id
6. Confirm no drag/drop, no POST forms
7. Run PHP syntax check
8. Confirm no forbidden files modified

## Pass Criteria
- Shell + design system render
- Read-only data displays
- Action links open controlled prototypes
- No writes from UX pages

## Signoff
Update M35_90 and M35_99 after user confirms.
